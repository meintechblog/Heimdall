const test = require("node:test");
const assert = require("node:assert/strict");
const { JSDOM } = require("jsdom");

const initFileFlowsTileControls = require("../../resources/assets/js/fileflowsTileControls");

function bootstrapTile() {
  const dom = new JSDOM(
    `<!doctype html>
    <html>
      <body>
        <section class="item-container" data-id="81">
          <div class="item">
            <div class="app-icon-container">
              <button
                type="button"
                class="fileflows-processing-toggle"
                data-id="81"
                data-toggle-url="/items/81/fileflows/toggle"
                aria-label="Pause FileFlows"
              >
                <i class="fas fa-pause"></i>
              </button>
            </div>
            <div class="details">
              <div class="livestats-container" data-id="81"></div>
            </div>
            <a class="link" href="http://fileflows.local"></a>
          </div>
        </section>`,
    { url: "http://localhost" }
  );

  global.window = dom.window;
  global.document = dom.window.document;
  global.Node = dom.window.Node;

  return dom;
}

test("updates the fileflows button to pause when the backend reports running", () => {
  bootstrapTile();
  const controls = initFileFlowsTileControls({ document });
  const container = document.querySelector(".livestats-container");

  controls.updateTileState(container, {
    processingState: "running",
    toggleAction: "pause",
  });

  const button = document.querySelector(".fileflows-processing-toggle");

  assert.equal(button.dataset.processingState, "running");
  assert.equal(button.dataset.toggleAction, "pause");
  assert.match(button.getAttribute("aria-label"), /Pause/i);
});

test("clicking the mini button posts to the FileFlows toggle endpoint without opening the tile", async () => {
  bootstrapTile();
  const fetchCalls = [];

  global.fetch = async (url, options) => {
    fetchCalls.push({ url, options });
    return {
      ok: true,
      json: async () => ({
        processingState: "paused",
        toggleAction: "resume",
      }),
    };
  };

  const controls = initFileFlowsTileControls({ document, fetch: global.fetch });
  controls.bindToggleButtons();

  const button = document.querySelector(".fileflows-processing-toggle");
  const clickEvent = new window.MouseEvent("click", {
    bubbles: true,
    cancelable: true,
  });

  button.dispatchEvent(clickEvent);
  await new Promise((resolve) => setTimeout(resolve, 0));

  assert.equal(fetchCalls.length, 1);
  assert.equal(fetchCalls[0].url, "/items/81/fileflows/toggle");
  assert.equal(fetchCalls[0].options.method, "POST");
  assert.equal(clickEvent.defaultPrevented, true);
  assert.equal(button.dataset.processingState, "paused");
  assert.equal(button.dataset.toggleAction, "resume");
});
