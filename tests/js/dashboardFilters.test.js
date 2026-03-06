const test = require("node:test");
const assert = require("node:assert/strict");
const { JSDOM } = require("jsdom");

const initDashboardFilters = require("../../resources/assets/js/dashboardFilters");

function bootstrapDashboard(provider = "google") {
  const dom = new JSDOM(
    `<!doctype html>
    <html>
      <body>
        <div id="app">
          <div id="main">
            <div class="searchform">
              <form>
                <div id="search-container" class="input-container">
                  <select name="provider">
                    <option value="tiles">Tiles</option>
                    <option value="google" selected="selected">Google</option>
                  </select>
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
                <section class="item-container tag-trading" data-name="Hulki Trading Deluxxe" data-id="149"></section>
              </div>
              <div class="category cat-media">
                <div class="title">Media</div>
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

  $("#search-container select[name=provider]").val(provider);

  initDashboardFilters({
    $,
    window,
    document: window.document,
  });

  return { window, $ };
}

test("filters dashboard tiles while typing even when provider is not tiles", () => {
  const { $ } = bootstrapDashboard("google");

  const visibleBefore = $("#sortable .item-container")
    .filter(function filterVisible() {
      return $(this).css("display") !== "none";
    })
    .map(function itemName() {
      return $(this).data("name");
    })
    .get();

  assert.deepEqual(visibleBefore, ["Hulki Trading Deluxxe", "Openclaw"]);

  $("#search-container input[name=q]").val("hulki").trigger("input");

  const visibleAfter = $("#sortable .item-container")
    .filter(function filterVisible() {
      return $(this).css("display") !== "none";
    })
    .map(function itemName() {
      return $(this).data("name");
    })
    .get();

  assert.deepEqual(visibleAfter, ["Hulki Trading Deluxxe"]);
});

test("category buttons keep filtering dashboard tiles when provider is not tiles", () => {
  const { $ } = bootstrapDashboard("google");

  $("#taglist .tag[data-tag='cat-media']").trigger("click");

  const visibleAfter = $("#sortable .item-container")
    .filter(function filterVisible() {
      return $(this).css("display") !== "none";
    })
    .map(function itemName() {
      return $(this).data("name");
    })
    .get();

  assert.deepEqual(visibleAfter, ["Openclaw"]);
});
