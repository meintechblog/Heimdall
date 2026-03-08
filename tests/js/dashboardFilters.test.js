const test = require("node:test");
const assert = require("node:assert/strict");
const { JSDOM } = require("jsdom");

const initDashboardFilters = require("../../resources/assets/js/dashboardFilters");

function bootstrapDashboard() {
  const dom = new JSDOM(
    `<!doctype html>
    <html>
      <body>
        <div id="app">
          <div id="main">
            <div class="searchform">
              <form>
                <div id="search-container" class="input-container">
                  <input type="text" name="q" value="" class="homesearch" />
                  <button type="submit">Suche</button>
                </div>
              </form>
            </div>
            <div id="taglist" class="taglist">
              <div class="tag white current" data-tag="all">All</div>
              <div class="tag white" data-tag="cat-finance">Finance</div>
              <div class="tag white" data-tag="cat-media">Media</div>
            </div>
            <div id="sortable">
              <div class="category cat-finance">
                <div class="title">Finance</div>
                <section class="item-container tag-trading tag-finance tag-media" data-name="Hulki Trading Deluxxe" data-id="149"></section>
              </div>
              <div class="category cat-media">
                <div class="title">Media</div>
                <section class="item-container tag-trading tag-finance tag-media" data-name="Hulki Trading Deluxxe" data-id="149"></section>
                <section class="item-container tag-media" data-name="Openclaw" data-id="150"></section>
              </div>
            </div>
          </div>
        </div>
      </body>
    </html>`,
    { url: "http://localhost" }
  );

  const { window } = dom;
  const jqueryFactory = require("jquery");
  const $ = jqueryFactory(window);

  window.$ = $;
  window.jQuery = $;
  global.window = window;
  global.document = window.document;
  global.localStorage = window.localStorage;
  global.$ = $;
  global.jQuery = $;

  initDashboardFilters({
    $,
    window,
    document: window.document,
  });

  return { window, $ };
}

function getVisibleItemNames($) {
  return $("#sortable .item-container, #dashboard-filter-results .item-container")
    .filter(function filterVisible() {
      const item = $(this);
      const hiddenAncestor = item
        .parents()
        .toArray()
        .some((element) => $(element).css("display") === "none");
      return item.css("display") !== "none" && hiddenAncestor === false;
    })
    .map(function itemName() {
      return $(this).data("name");
    })
    .get();
}

test("filters dashboard tiles while typing even when provider is not tiles", () => {
  const { $ } = bootstrapDashboard();

  const visibleBefore = getVisibleItemNames($);

  assert.deepEqual(visibleBefore, ["Hulki Trading Deluxxe", "Openclaw"]);

  $("#search-container input[name=q]").val("hulki").trigger("input");

  const visibleAfter = getVisibleItemNames($);

  assert.deepEqual(visibleAfter, ["Hulki Trading Deluxxe"]);
});

test("category buttons keep filtering dashboard tiles when provider is not tiles", () => {
  const { $ } = bootstrapDashboard();

  $("#taglist .tag[data-tag='cat-media']").trigger("click");

  const visibleAfter = getVisibleItemNames($);

  assert.deepEqual(visibleAfter, ["Hulki Trading Deluxxe", "Openclaw"]);
});

test("pressing enter with a query opens a google search in a new tab", () => {
  const { $, window } = bootstrapDashboard();
  const opened = [];

  window.open = (url, target) => {
    opened.push({ url, target });
  };

  $("#search-container input[name=q]").val("hulki trading");
  $(".searchform > form").trigger("submit");

  assert.deepEqual(opened, [
    {
      url: "https://www.google.com/search?q=hulki%20trading",
      target: "_blank",
    },
  ]);
});

test("duplicate tiles with the same item id are only shown once", () => {
  const { $ } = bootstrapDashboard();

  const visibleTradingTiles = $("#sortable .item-container")
    .filter(function filterVisible() {
      return (
        $(this).css("display") !== "none" && $(this).data("name") === "Hulki Trading Deluxxe"
      );
    })
    .map(function itemId() {
      return $(this).data("id");
    })
    .get();

  assert.deepEqual(visibleTradingTiles, [149]);
});

test("category button counts use unique visible matches per category", () => {
  const { $ } = bootstrapDashboard();

  const allLabel = $("#taglist .tag[data-tag='all']").text().trim();
  const financeLabel = $("#taglist .tag[data-tag='cat-finance']").text().trim();
  const mediaLabel = $("#taglist .tag[data-tag='cat-media']").text().trim();

  assert.equal(allLabel, "All (2)");
  assert.equal(financeLabel, "Finance (1)");
  assert.equal(mediaLabel, "Media (2)");
});

test("searching live-like duplicate categories shows one flat unique result set", () => {
  const dom = new JSDOM(
    `<!doctype html>
    <html>
      <body>
        <div id="app">
          <div id="main">
            <div class="searchform">
              <form>
                <div id="search-container" class="input-container">
                  <input type="text" name="q" value="" class="homesearch" />
                  <button type="submit">Suche</button>
                </div>
              </form>
            </div>
            <div id="taglist" class="taglist">
              <div class="tag white current" data-tag="all">All</div>
              <div class="tag white" data-tag="cat-main-services">Main Services</div>
              <div class="tag white" data-tag="cat-proxi1-32">Proxi1 3.2</div>
              <div class="tag white" data-tag="cat-proxi2-36">Proxi2 3.6</div>
              <div class="tag white" data-tag="cat-proxi3-316">Proxi3 3.16</div>
            </div>
            <div id="sortable">
              <div class="category cat-main-services">
                <div class="title">Main Services</div>
                <section class="item-container tag-main-services tag-proxi1-32" data-name="Proxi1 3.2:8006" data-id="14"></section>
                <section class="item-container tag-main-services tag-proxi2-36" data-name="Proxi2 3.6:8006" data-id="94"></section>
                <section class="item-container tag-main-services tag-proxi3-316" data-name="Proxi3 3.16:8006" data-id="122"></section>
              </div>
              <div class="category cat-proxi1-32">
                <div class="title">Proxi1 3.2</div>
                <section class="item-container tag-main-services tag-proxi1-32" data-name="Proxi1 3.2:8006" data-id="14"></section>
              </div>
              <div class="category cat-proxi2-36">
                <div class="title">Proxi2 3.6</div>
                <section class="item-container tag-main-services tag-proxi2-36" data-name="Proxi2 3.6:8006" data-id="94"></section>
              </div>
              <div class="category cat-proxi3-316">
                <div class="title">Proxi3 3.16</div>
                <section class="item-container tag-main-services tag-proxi3-316" data-name="Proxi3 3.16:8006" data-id="122"></section>
              </div>
            </div>
          </div>
        </div>
      </body>
    </html>`,
    { url: "http://localhost" }
  );

  const { window } = dom;
  const jqueryFactory = require("jquery");
  const $ = jqueryFactory(window);

  window.$ = $;
  window.jQuery = $;
  global.window = window;
  global.document = window.document;
  global.localStorage = window.localStorage;
  global.$ = $;
  global.jQuery = $;

  initDashboardFilters({
    $,
    window,
    document: window.document,
  });

  $("#search-container input[name=q]").val("proxi").trigger("input");

  const visibleResults = $("#dashboard-filter-results .item-container")
    .filter(function filterVisible() {
      return $(this).css("display") !== "none";
    })
    .map(function itemName() {
      return $(this).data("name");
    })
    .get();

  assert.deepEqual(visibleResults, [
    "Proxi1 3.2:8006",
    "Proxi2 3.6:8006",
    "Proxi3 3.16:8006",
  ]);
  assert.notEqual($("#dashboard-filter-results").css("display"), "none");
  assert.equal($("#sortable").css("display"), "none");
});
