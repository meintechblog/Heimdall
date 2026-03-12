const test = require("node:test");
const assert = require("node:assert/strict");

const {
  createActivityTracker,
  createVisibilityTracker,
  scheduleVisibleContainers,
  getInitialRequestDelay,
  queueInitialUpdates,
} = require("../../resources/assets/js/liveStatRefresh");

test("spreads initial live-stat requests across short staggered delays", () => {
  const delays = [];
  const scheduledContainers = [];

  queueInitialUpdates(
    ["one", "two", "three"],
    (container) => {
      scheduledContainers.push(container);
    },
    (callback, delay) => {
      delays.push(delay);
      callback();
    }
  );

  assert.equal(getInitialRequestDelay(0), 0);
  assert.equal(getInitialRequestDelay(1), 150);
  assert.equal(getInitialRequestDelay(2), 300);
  assert.deepEqual(delays, [0, 150, 300]);
  assert.deepEqual(scheduledContainers, ["one", "two", "three"]);
});

test("tracks visible containers and triggers updates when they enter the viewport", () => {
  const visibleCallbacks = [];
  let observerCallback = null;

  const firstContainer = {
    getAttribute: () => "1",
    getBoundingClientRect: () => ({ top: 0, bottom: 50 }),
  };
  const secondContainer = {
    getAttribute: () => "2",
    getBoundingClientRect: () => ({ top: 2000, bottom: 2100 }),
  };

  const tracker = createVisibilityTracker([firstContainer, secondContainer], {
    window: { innerHeight: 800 },
    observerFactory: (callback) => {
      observerCallback = callback;
      return {
        observe() {},
        disconnect() {},
      };
    },
  });

  tracker.setVisibleCallback((container) => {
    visibleCallbacks.push(container.getAttribute("data-id"));
  });

  assert.equal(tracker.isVisible(firstContainer), true);
  assert.equal(tracker.isVisible(secondContainer), false);

  observerCallback([
    {
      target: secondContainer,
      isIntersecting: true,
    },
  ]);

  assert.equal(tracker.isVisible(secondContainer), true);
  assert.deepEqual(visibleCallbacks, ["2"]);
});

test("tracks whether the browser tab is active and triggers a resume callback", () => {
  const listeners = {};
  const activityCallbacks = [];
  const fakeDocument = {
    hidden: true,
    addEventListener(eventName, callback) {
      listeners[eventName] = callback;
    },
  };

  const tracker = createActivityTracker(fakeDocument);
  tracker.setActiveCallback(() => {
    activityCallbacks.push("active");
  });

  assert.equal(tracker.isActive(), false);

  fakeDocument.hidden = false;
  listeners.visibilitychange();

  assert.equal(tracker.isActive(), true);
  assert.deepEqual(activityCallbacks, ["active"]);
});

test("only schedules visible containers while the tab is active", () => {
  const scheduled = [];
  const visibleContainer = { name: "visible" };
  const hiddenContainer = { name: "hidden" };

  scheduleVisibleContainers(
    [visibleContainer, hiddenContainer],
    {
      isVisible(container) {
        return container === visibleContainer;
      },
    },
    {
      isActive() {
        return true;
      },
    },
    (container) => {
      scheduled.push(container.name);
    }
  );

  assert.deepEqual(scheduled, ["visible"]);
});
