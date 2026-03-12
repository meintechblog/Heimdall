const test = require("node:test");
const assert = require("node:assert/strict");
const { JSDOM } = require("jsdom");

const initDiscoveryPanel = require("../../resources/assets/js/discoveryPanel");

function createDiscoveryDom() {
  const dom = new JSDOM(
    `<!doctype html>
    <html>
      <body>
        <section
          id="discovery-hub"
          class="discovery-hub"
          data-summary-url="/discoveries/summary"
          data-candidates-url="/discoveries/candidates"
          data-add-url="/discoveries/items"
        >
          <div class="discovery-toolbar">
            <button
              type="button"
              id="discovery-toggle"
              class="discovery-toggle is-hidden"
              aria-expanded="false"
            >
              <span class="discovery-toggle-count" data-role="count"></span>
              <span class="discovery-toggle-icon" data-role="icon">+</span>
            </button>
          </div>
          <div class="discovery-panel is-hidden" data-role="panel">
            <div class="discovery-candidate-list" data-role="candidates"></div>
            <div class="discovery-panel-state" data-role="state"></div>
          </div>
        </section>
      </body>
    </html>`,
    { url: "http://localhost" }
  );

  global.window = dom.window;
  global.document = dom.window.document;
  global.Node = dom.window.Node;

  return dom;
}

function flush() {
  return new Promise((resolve) => setTimeout(resolve, 0));
}

test("shows the plus button after discovery finds new devices and renders candidates on open", async () => {
  createDiscoveryDom();

  const fetchCalls = [];
  const fetchMock = async (url) => {
    fetchCalls.push(url);

    if (url === "/discoveries/summary") {
      return {
        ok: true,
        json: async () => ({
          totalCount: 1,
          sources: [{ key: "wled", label: "WLED", count: 1 }],
        }),
      };
    }

    if (url === "/discoveries/candidates") {
      return {
        ok: true,
        json: async () => ({
          totalCount: 1,
          candidates: [
            {
              id: "candidate-1",
              source: "wled",
              sourceLabel: "WLED",
              title: "Hall Strip",
              subtitle: "WLED 0.14.4",
              host: "192.168.3.60",
              url: "http://192.168.3.60",
              iconUrl: "/storage/icons/wled.png",
            },
          ],
        }),
      };
    }

    throw new Error(`Unexpected URL: ${url}`);
  };

  const discovery = initDiscoveryPanel({
    document,
    window,
    fetch: fetchMock,
    scheduleInterval: () => 1,
    autoStart: false,
  });

  await discovery.refreshSummary();

  const toggle = document.getElementById("discovery-toggle");

  assert.equal(toggle.classList.contains("is-hidden"), false);
  assert.equal(
    document.querySelector('[data-role="count"]').textContent.trim(),
    "1"
  );
  assert.equal(toggle.getAttribute("aria-expanded"), "false");

  toggle.dispatchEvent(
    new window.MouseEvent("click", { bubbles: true, cancelable: true })
  );
  await flush();

  assert.equal(toggle.getAttribute("aria-expanded"), "true");
  assert.equal(document.querySelector('[data-role="icon"]').textContent, "-");
  assert.match(
    document.querySelector('[data-role="candidates"]').textContent,
    /Hall Strip/
  );
  assert.deepEqual(fetchCalls, [
    "/discoveries/summary",
    "/discoveries/candidates",
  ]);
});

test("adds a prepared discovery tile as a normal item and reloads the dashboard", async () => {
  createDiscoveryDom();

  const reloads = [];
  const fetchMock = async (url, options = {}) => {
    if (url === "/discoveries/summary") {
      return {
        ok: true,
        json: async () => ({
          totalCount: 1,
          sources: [{ key: "wled", label: "WLED", count: 1 }],
        }),
      };
    }

    if (url === "/discoveries/candidates") {
      return {
        ok: true,
        json: async () => ({
          totalCount: 1,
          candidates: [
            {
              id: "candidate-1",
              source: "wled",
              sourceLabel: "WLED",
              title: "Hall Strip",
              subtitle: "WLED 0.14.4",
              host: "192.168.3.60",
              url: "http://192.168.3.60",
              iconUrl: "/storage/icons/wled.png",
            },
          ],
        }),
      };
    }

    if (url === "/discoveries/items") {
      assert.equal(options.method, "POST");
      assert.match(String(options.body), /candidate-1/);

      return {
        ok: true,
        json: async () => ({
          created: true,
          item: {
            id: 99,
            title: "Hall Strip",
            url: "http://192.168.3.60",
          },
        }),
      };
    }

    throw new Error(`Unexpected URL: ${url}`);
  };

  const discovery = initDiscoveryPanel({
    document,
    window: {
      location: {
        reload() {
          reloads.push("reload");
        },
      },
    },
    fetch: fetchMock,
    scheduleInterval: () => 1,
    autoStart: false,
  });

  await discovery.refreshSummary();

  document.getElementById("discovery-toggle").dispatchEvent(
    new window.MouseEvent("click", { bubbles: true, cancelable: true })
  );
  await flush();

  document
    .querySelector(".discovery-candidate")
    .dispatchEvent(
      new window.MouseEvent("click", { bubbles: true, cancelable: true })
    );
  await flush();

  assert.deepEqual(reloads, ["reload"]);
});
