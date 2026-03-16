const test = require("node:test");
const assert = require("node:assert/strict");
const { JSDOM } = require("jsdom");

const initDiscoveryPanel = require("../../resources/assets/js/discoveryPanel");

function createDiscoveryDom() {
  const dom = new JSDOM(
    `<!doctype html>
    <html>
      <body>
        <div class="search-discovery-shell">
          <div class="search-discovery-row">
            <div class="searchform">
              <form action="https://www.google.com/search" target="_blank" method="get">
                <div id="search-container" class="input-container">
                  <input type="text" class="homesearch" />
                  <button type="submit">Suche</button>
                </div>
              </form>
            </div>
            <section
              id="discovery-hub"
              class="discovery-hub"
              data-summary-url="/discoveries/summary"
              data-candidates-url="/discoveries/candidates"
              data-progress-url="/discoveries/progress"
              data-add-url="/discoveries/items"
            >
              <button
                type="button"
                id="discovery-toggle"
                class="discovery-toggle is-hidden"
                aria-expanded="false"
              >
                <span class="discovery-toggle-count" data-role="count"></span>
                <span class="discovery-toggle-icon" data-role="icon">+</span>
              </button>
            </section>
          </div>
          <div class="discovery-panel is-hidden" data-role="panel">
            <div class="discovery-candidate-list" data-role="candidates"></div>
            <div class="discovery-panel-state is-hidden" data-role="state"></div>
          </div>
        </div>
      </body>
    </html>`,
    { url: "http://192.168.3.88" }
  );

  global.window = dom.window;
  global.document = dom.window.document;
  global.Node = dom.window.Node;
  Object.defineProperty(dom.window.document, "hidden", {
    configurable: true,
    value: false,
  });

  return dom;
}

function flush() {
  return new Promise((resolve) => setTimeout(resolve, 0));
}

test("shows the plus button and renders separate open and add actions", async () => {
  createDiscoveryDom();

  const fetchCalls = [];
  const fetchMock = async (url) => {
    fetchCalls.push(url);

    if (url === "/discoveries/summary") {
      return {
        ok: true,
        json: async () => ({
          totalCount: 2,
          sources: [
            { key: "wled", label: "WLED", count: 1 },
            { key: "espresense", label: "ESPresense", count: 1 },
          ],
        }),
      };
    }

    if (url === "/discoveries/candidates") {
      return {
        ok: true,
        json: async () => ({
          totalCount: 2,
          completedSources: 3,
          totalSources: 3,
          isComplete: true,
          candidates: [
            {
              id: "candidate-1",
              source: "wled",
              sourceLabel: "WLED",
              title: "wled-buero2",
              subtitle: "192.168.3.64 · WLED 0.14.1",
              host: "192.168.3.64",
              url: "http://192.168.3.64",
              iconUrl: "/storage/icons/wled.png",
            },
            {
              id: "candidate-2",
              source: "espresense",
              sourceLabel: "ESPresense",
              title: "Kueche",
              subtitle: "192.168.3.239 · Raum",
              host: "192.168.3.239",
              url: "http://192.168.3.239",
              iconUrl: "/storage/icons/espresense.svg",
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
  assert.equal(toggle.closest(".search-discovery-row") !== null, true);

  toggle.dispatchEvent(
    new window.MouseEvent("click", { bubbles: true, cancelable: true })
  );
  await flush();

  assert.equal(toggle.getAttribute("aria-expanded"), "true");
  assert.equal(document.querySelector('[data-role="icon"]').textContent, "-");
  assert.match(
    document.querySelector('[data-role="candidates"]').textContent,
    /wled-buero2/
  );
  assert.match(
    document.querySelector('[data-role="candidates"]').textContent,
    /Kueche/
  );
  assert.equal(
    document.querySelectorAll(".discovery-candidate-open").length,
    2
  );
  assert.equal(
    document.querySelectorAll(".discovery-candidate-add").length,
    2
  );
  assert.equal(
    document.querySelector(".discovery-candidate-add").textContent.trim(),
    "Hinzufügen"
  );
  assert.equal(
    document
      .querySelector(".discovery-candidate-open")
      .getAttribute("aria-label"),
    "wled-buero2 öffnen"
  );
  assert.deepEqual(fetchCalls, [
    "/discoveries/summary",
    "/discoveries/candidates",
  ]);
});

test("opens a prepared discovery card without triggering add", async () => {
  createDiscoveryDom();

  const fetchCalls = [];
  const opens = [];
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
          completedSources: 1,
          totalSources: 1,
          isComplete: true,
          candidates: [
            {
              id: "candidate-1",
              source: "wled",
              sourceLabel: "WLED",
              title: "wled-buero2",
              subtitle: "192.168.3.64 · WLED 0.14.1",
              host: "192.168.3.64",
              url: "http://192.168.3.64",
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
    window: {
      open(url, target) {
        opens.push([url, target]);
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
  await flush();

  document
    .querySelector(".discovery-candidate-open")
    .dispatchEvent(
      new window.MouseEvent("click", { bubbles: true, cancelable: true })
    );

  assert.deepEqual(opens, [["http://192.168.3.64", "_blank"]]);
  assert.deepEqual(fetchCalls, [
    "/discoveries/summary",
    "/discoveries/candidates",
  ]);
});

test("keeps the discovery list open so multiple candidates can be added without a reload", async () => {
  createDiscoveryDom();

  const addBodies = [];
  const fetchMock = async (url, options = {}) => {
    if (url === "/discoveries/summary") {
      return {
        ok: true,
        json: async () => ({
          totalCount: 2,
          sources: [{ key: "wled", label: "WLED", count: 2 }],
        }),
      };
    }

    if (url === "/discoveries/candidates") {
      return {
        ok: true,
        json: async () => ({
          totalCount: 2,
          candidates: [
            {
              id: "candidate-1",
              source: "wled",
              sourceLabel: "WLED",
              title: "wled-buero2",
              subtitle: "192.168.3.64 · WLED 0.14.1",
              host: "192.168.3.64",
              url: "http://192.168.3.64",
              iconUrl: "/storage/icons/wled.png",
            },
            {
              id: "candidate-2",
              source: "wled",
              sourceLabel: "WLED",
              title: "wled-wohnzimmer",
              subtitle: "192.168.3.65 · WLED 0.14.1",
              host: "192.168.3.65",
              url: "http://192.168.3.65",
              iconUrl: "/storage/icons/wled.png",
            },
          ],
        }),
      };
    }

    if (url === "/discoveries/items") {
      assert.equal(options.method, "POST");
      addBodies.push(String(options.body));

      return {
        ok: true,
        json: async () => ({
          created: true,
          item: {
            id: 99,
            title: "wled-buero2",
            url: "http://192.168.3.64",
          },
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

  document.getElementById("discovery-toggle").dispatchEvent(
    new window.MouseEvent("click", { bubbles: true, cancelable: true })
  );
  await flush();

  document
    .querySelector(".discovery-candidate-add")
    .dispatchEvent(
      new window.MouseEvent("click", { bubbles: true, cancelable: true })
    );
  await flush();
  await flush();

  assert.equal(
    document.querySelectorAll(".discovery-candidate").length,
    1
  );
  assert.match(
    document.querySelector('[data-role="candidates"]').textContent,
    /wled-wohnzimmer/
  );
  assert.equal(
    document.querySelector('[data-role="count"]').textContent,
    "1"
  );

  document
    .querySelector(".discovery-candidate-add")
    .dispatchEvent(
      new window.MouseEvent("click", { bubbles: true, cancelable: true })
    );
  await flush();
  await flush();

  assert.equal(
    document.querySelectorAll(".discovery-candidate").length,
    0
  );
  assert.equal(
    document.querySelector('[data-role="panel"]').classList.contains("is-hidden"),
    true
  );
  assert.equal(
    document.getElementById("discovery-toggle").classList.contains("is-hidden"),
    true
  );
  assert.deepEqual(addBodies, [
    JSON.stringify({ source: "wled", candidateId: "candidate-1" }),
    JSON.stringify({ source: "wled", candidateId: "candidate-2" }),
  ]);
});

test("escapes discovery candidate content before rendering", async () => {
  createDiscoveryDom();

  const fetchMock = async (url) => {
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
          completedSources: 1,
          totalSources: 1,
          isComplete: true,
          candidates: [
            {
              id: 'candidate-1" onclick="alert(1)',
              source: "wled",
              sourceLabel: 'WLED"><img src=x data-injected="label">',
              title: '<img src=x data-injected="title">',
              subtitle: '<img src=x data-injected="subtitle">',
              host: "192.168.3.64",
              url: 'http://192.168.3.64" data-injected="url',
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
  document.getElementById("discovery-toggle").dispatchEvent(
    new window.MouseEvent("click", { bubbles: true, cancelable: true })
  );
  await flush();

  const candidates = document.querySelector('[data-role="candidates"]');
  assert.equal(
    candidates.querySelector('[data-injected="title"]'),
    null
  );
  assert.equal(
    candidates.querySelector('[data-injected="subtitle"]'),
    null
  );
  assert.equal(
    candidates.querySelector('[data-injected="label"]'),
    null
  );
  assert.match(candidates.textContent, /<img src=x data-injected="title">/);
});

test("renders discovery candidates progressively while a fresh scan is running", async () => {
  createDiscoveryDom();

  const fetchCalls = [];
  const fetchMock = async (url) => {
    fetchCalls.push(url);

    if (url === "/discoveries/summary") {
      return {
        ok: true,
        json: async () => ({
          totalCount: 2,
          sources: [{ key: "wled", label: "WLED", count: 2 }],
        }),
      };
    }

    if (url === "/discoveries/candidates") {
      return {
        ok: true,
        json: async () => ({
          totalCount: 2,
          completedSources: 3,
          totalSources: 3,
          isComplete: true,
          candidates: [
            {
              id: "candidate-1",
              source: "wled",
              sourceLabel: "WLED",
              title: "wled-buero2",
              subtitle: "192.168.3.64 · WLED 0.14.1",
              host: "192.168.3.64",
              url: "http://192.168.3.64",
              iconUrl: "/storage/icons/wled.png",
            },
            {
              id: "candidate-2",
              source: "espresense",
              sourceLabel: "ESPresense",
              title: "Kueche",
              subtitle: "192.168.3.239 · Raum",
              host: "192.168.3.239",
              url: "http://192.168.3.239",
              iconUrl: "/storage/icons/espresense.svg",
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

  document.getElementById("discovery-toggle").dispatchEvent(
    new window.MouseEvent("click", { bubbles: true, cancelable: true })
  );
  await flush();

  assert.match(
    document.querySelector('[data-role="candidates"]').textContent,
    /wled-buero2/
  );
  assert.match(
    document.querySelector('[data-role="candidates"]').textContent,
    /Kueche/
  );
  assert.deepEqual(fetchCalls, [
    "/discoveries/summary",
    "/discoveries/candidates",
  ]);
});
