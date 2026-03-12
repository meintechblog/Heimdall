const REFRESH_INTERVAL_SMALL = 5000;
const REFRESH_INTERVAL_BIG = 30000;
const QUEUE_PROCESSING_INTERVAL = 1000;
const INITIAL_REQUEST_STAGGER_MS = 150;
const CONTAINER_SELECTOR = ".livestats-container";
const fileFlowsTileControls =
  typeof window !== "undefined" &&
  typeof window.initHeimdallFileFlowsTileControls === "function"
    ? window.initHeimdallFileFlowsTileControls({
        document,
        fetch: window.fetch.bind(window),
      })
    : null;

/**
 * @returns {*[]}
 */
function createQueue() {
  const queue = [];
  let suspended = false;

  function processQueue() {
    if (queue.length === 0 || suspended === true) {
      return;
    }

    const next = queue.shift();
    next();
  }

  document.addEventListener("visibilitychange", () => {
    suspended = document.hidden;
  });

  setInterval(processQueue, QUEUE_PROCESSING_INTERVAL);

  return queue;
}

/**
 * @returns {NodeListOf<Element>}
 */
function getContainers() {
  return document.querySelectorAll(CONTAINER_SELECTOR);
}

/**
 *
 * @param {boolean} dataOnly
 * @param {boolean} active
 * @returns {number}
 */
function getQueueInterval(dataOnly, active) {
  if (dataOnly) {
    return REFRESH_INTERVAL_BIG;
  }

  if (active) {
    return REFRESH_INTERVAL_SMALL;
  }

  return REFRESH_INTERVAL_BIG;
}

function getInitialRequestDelay(index) {
  return index * INITIAL_REQUEST_STAGGER_MS;
}

function queueInitialUpdates(containers, enqueueUpdate, schedule = setTimeout) {
  Array.from(containers).forEach((container, index) => {
    schedule(() => {
      enqueueUpdate(container);
    }, getInitialRequestDelay(index));
  });
}

function isElementInViewport(
  element,
  view = typeof window !== "undefined" ? window : null
) {
  if (!view || typeof element.getBoundingClientRect !== "function") {
    return true;
  }

  const rect = element.getBoundingClientRect();
  const viewportHeight = view.innerHeight || 0;

  return rect.bottom >= 0 && rect.top <= viewportHeight;
}

function createVisibilityTracker(containers, options = {}) {
  const view =
    options.window || (typeof window !== "undefined" ? window : null);
  const observerFactory =
    options.observerFactory ||
    (typeof IntersectionObserver !== "undefined"
      ? (callback, observerOptions) =>
          new IntersectionObserver(callback, observerOptions)
      : null);
  const visibilityState = new WeakMap();
  let visibleCallback = null;
  let observer = null;

  if (observerFactory) {
    observer = observerFactory(
      (entries) => {
        entries.forEach((entry) => {
          visibilityState.set(entry.target, entry.isIntersecting);

          if (entry.isIntersecting && visibleCallback) {
            visibleCallback(entry.target);
          }
        });
      },
      {
        rootMargin: "200px 0px",
      }
    );

    Array.from(containers).forEach((container) => {
      observer.observe(container);
    });
  }

  return {
    isVisible(container) {
      if (visibilityState.has(container)) {
        return visibilityState.get(container);
      }

      return isElementInViewport(container, view);
    },
    setVisibleCallback(callback) {
      visibleCallback = callback;
    },
    disconnect() {
      if (observer && typeof observer.disconnect === "function") {
        observer.disconnect();
      }
    },
  };
}

function createUpdateScheduler(queue, createJob, schedule = setTimeout) {
  const scheduledIds = new Set();

  return (container, delay = 0) => {
    const containerId = container.getAttribute("data-id");

    if (scheduledIds.has(containerId)) {
      return;
    }

    scheduledIds.add(containerId);

    schedule(() => {
      queue.push(() => {
        scheduledIds.delete(containerId);
        return createJob(container)();
      });
    }, delay);
  };
}

/**
 * @param {HTMLElement} container
 * @param {Function} scheduleUpdate
 * @param {{isVisible: function(HTMLElement): boolean}|null} visibilityTracker
 * @returns {function(): Promise<Response>}
 */
function createUpdateJob(container, scheduleUpdate, visibilityTracker) {
  const id = container.getAttribute("data-id");
  // Data only attribute seems to indicate that the item should not be updated that often
  const isDataOnly = container.getAttribute("data-dataonly") === "1";

  return () => {
    if (visibilityTracker && !visibilityTracker.isVisible(container)) {
      return Promise.resolve();
    }

    return fetch(`get_stats/${id}`)
      .then((response) => {
        if (response.ok) {
          return response.json();
        }

        throw new Error(`Network response was not ok: ${response.status}`);
      })
      .then((data) => {
        // eslint-disable-next-line no-param-reassign
        container.innerHTML = data.html;

        if (fileFlowsTileControls && data.processingState) {
          fileFlowsTileControls.updateTileState(container, data);
        }

        const isActive = data.status === "active";

        if (scheduleUpdate) {
          scheduleUpdate(container, getQueueInterval(isDataOnly, isActive));
        }
      })
      .catch((error) => {
        // eslint-disable-next-line no-console
        console.error(error);
        if (scheduleUpdate) {
          scheduleUpdate(container, REFRESH_INTERVAL_BIG);
        }
      });
  };
}

if (typeof module === "object" && module.exports) {
  module.exports = {
    createVisibilityTracker,
    getInitialRequestDelay,
    queueInitialUpdates,
  };
}

if (typeof document !== "undefined") {
  const livestatContainers = getContainers();

  if (livestatContainers.length > 0) {
    const myQueue = createQueue();
    const visibilityTracker = createVisibilityTracker(livestatContainers);
    const scheduleUpdate = createUpdateScheduler(myQueue, (container) =>
      createUpdateJob(container, scheduleUpdate, visibilityTracker)
    );

    visibilityTracker.setVisibleCallback((container) => {
      scheduleUpdate(container);
    });

    queueInitialUpdates(livestatContainers, (container) => {
      scheduleUpdate(container);
    });
  }
}
