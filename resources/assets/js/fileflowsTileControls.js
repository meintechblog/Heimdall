(function registerFileFlowsTileControls(root, factory) {
  if (typeof module === "object" && module.exports) {
    module.exports = factory;
  }

  const rootScope = root;
  if (rootScope) {
    rootScope.initHeimdallFileFlowsTileControls = factory;
  }
})(typeof window !== "undefined" ? window : undefined, (options = {}) => {
  const doc = options.document || document;
  const fetchImpl =
    options.fetch ||
    (typeof window !== "undefined" && window.fetch
      ? window.fetch.bind(window)
      : null);
  let bound = false;

  function getButtonIcon(processingState) {
    return processingState === "paused" ? "pause" : "play";
  }

  function getButtonMarkup(processingState) {
    const icon = getButtonIcon(processingState);

    if (icon === "play") {
      return `
        <svg class="fileflows-toggle-icon fileflows-toggle-icon-play" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
          <path d="M8 6.5v11l9-5.5-9-5.5z"></path>
        </svg>
      `;
    }

    return `
      <svg class="fileflows-toggle-icon fileflows-toggle-icon-pause" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <rect x="7" y="6" width="4" height="12" rx="1"></rect>
        <rect x="13" y="6" width="4" height="12" rx="1"></rect>
      </svg>
    `;
  }

  function getButtonLabel(toggleAction) {
    return toggleAction === "resume" ? "Resume FileFlows" : "Pause FileFlows";
  }

  function getButtonForElement(element) {
    const itemContainer = element.closest(".item-container");

    if (!itemContainer) {
      return null;
    }

    return itemContainer.querySelector(".fileflows-processing-toggle");
  }

  function updateTileState(element, state = {}) {
    const button = getButtonForElement(element);

    if (!button || !state.processingState || !state.toggleAction) {
      return null;
    }

    button.dataset.processingState = state.processingState;
    button.dataset.toggleAction = state.toggleAction;
    button.setAttribute("aria-label", getButtonLabel(state.toggleAction));
    button.setAttribute("title", getButtonLabel(state.toggleAction));
    button.classList.toggle("is-paused", state.processingState === "paused");
    button.classList.toggle("is-running", state.processingState === "running");
    button.disabled = false;
    button.innerHTML = getButtonMarkup(state.processingState).trim();

    return button;
  }

  async function handleToggleClick(event) {
    const button = event.target.closest(".fileflows-processing-toggle");

    if (!button) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();

    if (!fetchImpl || button.disabled) {
      return;
    }

    button.disabled = true;

    try {
      const response = await fetchImpl(button.dataset.toggleUrl, {
        method: "POST",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      if (!response.ok) {
        throw new Error(`Toggle failed: ${response.status}`);
      }

      const data = await response.json();
      updateTileState(button, data);
    } catch (error) {
      button.disabled = false;
      // eslint-disable-next-line no-console
      console.error(error);
    }
  }

  function bindToggleButtons() {
    if (bound) {
      return;
    }

    doc.addEventListener("click", handleToggleClick);
    bound = true;
  }

  bindToggleButtons();

  return {
    bindToggleButtons,
    updateTileState,
  };
});
