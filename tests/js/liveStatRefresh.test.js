const test = require("node:test");
const assert = require("node:assert/strict");

const {
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
