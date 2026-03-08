(function registerDashboardFilters(root, factory) {
  if (typeof module === "object" && module.exports) {
    module.exports = factory;
  }

  const rootScope = root;
  if (rootScope) {
    rootScope.initHeimdallDashboardFilters = factory;
  }
})(typeof window !== "undefined" ? window : undefined, (options) => {
  const { $, storage: providedStorage, window: browserWindow } = options;
  const storage = providedStorage || (browserWindow || {}).localStorage || null;
  const TAG_USAGE_STORAGE_KEY = "heimdall.tagUsageHistory";
  const TAG_USAGE_WINDOW = 100;
  const ALL_TAG = "all";

  function getItemTagForFilter(tag) {
    const normalizedTag = String(tag || "");

    if (normalizedTag.startsWith("cat-")) {
      return `tag-${normalizedTag.slice(4)}`;
    }

    return normalizedTag;
  }

  function getTagUsageHistory() {
    if (!storage) {
      return [];
    }

    try {
      const raw = JSON.parse(storage.getItem(TAG_USAGE_STORAGE_KEY) || "[]");
      if (Array.isArray(raw)) {
        return raw.filter((entry) => typeof entry === "string");
      }

      if (raw && typeof raw === "object") {
        const expanded = [];
        Object.keys(raw).forEach((tag) => {
          const count = Number(raw[tag] || 0);
          for (let i = 0; i < count; i += 1) {
            expanded.push(tag);
          }
        });
        return expanded.slice(-TAG_USAGE_WINDOW);
      }
    } catch (_error) {
      return [];
    }

    return [];
  }

  function saveTagUsageHistory(history) {
    if (!storage) {
      return;
    }

    try {
      storage.setItem(
        TAG_USAGE_STORAGE_KEY,
        JSON.stringify(history.slice(-TAG_USAGE_WINDOW))
      );
    } catch (_error) {
      // Ignore localStorage failures and continue with default ordering.
    }
  }

  function getTagUsageCounts() {
    const counts = {};

    getTagUsageHistory().forEach((tag) => {
      counts[tag] = (counts[tag] || 0) + 1;
    });

    return counts;
  }

  function getSearchValue() {
    return String(
      $("#search-container input[name=q]").val() || ""
    ).toLowerCase();
  }

  function getRawSearchValue() {
    return String($("#search-container input[name=q]").val() || "").trim();
  }

  function getSelectedTag() {
    return (
      String($("#taglist .tag.current").first().data("tag") || ALL_TAG) ||
      ALL_TAG
    );
  }

  function getVisibleItems() {
    return $("#sortable .item-container").filter(function filterVisible() {
      return $(this).css("display") !== "none";
    });
  }

  function ensureFilteredResultsContainer(sortable) {
    let results = $("#dashboard-filter-results");

    if (results.length === 0) {
      results = $(
        '<div id="dashboard-filter-results" class="categories"></div>'
      );
      sortable.after(results);
    }

    return results;
  }

  function uniqueElementsByItemId(elements) {
    const seen = new Set();

    return elements.filter(function filterDuplicates() {
      const itemId = String($(this).data("id") || "");

      if (itemId === "") {
        return true;
      }
      if (seen.has(itemId)) {
        return false;
      }

      seen.add(itemId);
      return true;
    });
  }

  function itemMatchesFilters(item, search, selectedTag) {
    const name = String(item.data("name") || "").toLowerCase();
    const matchesSearch = search.length === 0 || name.includes(search);
    const filterTag = getItemTagForFilter(selectedTag);
    const matchesTag =
      selectedTag === ALL_TAG ||
      (filterTag.length > 0 && item.hasClass(filterTag));

    return matchesSearch && matchesTag;
  }

  function updateTagButtonCounts() {
    const items = $("#sortable").find(".item-container");
    const canonicalItems = uniqueElementsByItemId(items);
    const search = getSearchValue();
    const datasetItems = canonicalItems.filter(function filterBySearch() {
      return itemMatchesFilters($(this), search, ALL_TAG);
    });

    $("#taglist .tag").each(function updateCount() {
      const button = $(this);
      const tag = button.data("tag");

      if (button.data("base-text") === undefined) {
        const baseText = button
          .text()
          .replace(/\s*\(\d+\)\s*$/, "")
          .trim();
        button.data("base-text", baseText);
      }

      const baseText = button.data("base-text");
      const count =
        tag === ALL_TAG
          ? datasetItems.length
          : datasetItems.filter(function filterByTag() {
              return itemMatchesFilters($(this), "", String(tag));
            }).length;

      button.text(`${baseText} (${count})`);
    });
  }

  function reorderTagButtonsByUsage() {
    const taglist = $("#taglist");
    if (taglist.length === 0) {
      return;
    }

    const usage = getTagUsageCounts();
    const buttons = taglist.find(".tag").get();

    buttons.sort((firstButton, secondButton) => {
      const first = $(firstButton);
      const second = $(secondButton);
      const firstTag = String(first.data("tag") || "");
      const secondTag = String(second.data("tag") || "");

      if (firstTag === ALL_TAG) return -1;
      if (secondTag === ALL_TAG) return 1;

      const usageDelta = (usage[secondTag] || 0) - (usage[firstTag] || 0);
      if (usageDelta !== 0) {
        return usageDelta;
      }

      return (
        (first.data("order-index") || 0) - (second.data("order-index") || 0)
      );
    });

    buttons.forEach((button) => {
      taglist.append(button);
    });
  }

  function applyTileFilters() {
    const sortable = $("#sortable");
    const filteredResults = ensureFilteredResultsContainer(sortable);
    const items = sortable.find(".item-container");
    const categoryWrappers = sortable.find(".category");
    const categoryTitles = sortable.find(".category > .title");
    const search = getSearchValue();
    const selectedTag = getSelectedTag();
    const filterActive = search.length > 0 || selectedTag !== ALL_TAG;

    if (filterActive) {
      const filteredItems = uniqueElementsByItemId(
        items.filter(function filterItems() {
          return itemMatchesFilters($(this), search, selectedTag);
        })
      );

      filteredResults.empty();
      filteredItems.each(function appendFilteredItem() {
        filteredResults.append($(this).clone(true, true).show());
      });

      sortable.hide();
      filteredResults.css("display", "flex");
      updateTagButtonCounts();
      return;
    }

    filteredResults.empty().hide();
    sortable.css("display", "flex");

    items.hide();
    items
      .filter(function filterItems() {
        return itemMatchesFilters($(this), search, selectedTag);
      })
      .show();

    const visibleItems = getVisibleItems();
    const uniqueVisibleItems = uniqueElementsByItemId(visibleItems);
    visibleItems.not(uniqueVisibleItems).hide();

    if (categoryWrappers.length > 0) {
      categoryWrappers.show();

      categoryWrappers.each(function updateWrapperVisibility() {
        const wrapper = $(this);
        const matchingChildren = wrapper
          .find(".item-container")
          .filter(function filterVisibleChildren() {
            return $(this).css("display") !== "none";
          }).length;
        wrapper.toggle(matchingChildren > 0);
      });

      if (selectedTag !== ALL_TAG || search.length > 0) {
        categoryTitles.hide();
      } else {
        categoryTitles.show();
      }
    }

    updateTagButtonCounts();
  }

  $("#taglist .tag").each(function storeOrderIndex(index) {
    $(this).data("order-index", index);
  });

  reorderTagButtonsByUsage();

  $(".searchform > form").on("submit.dashboardFilters", (event) => {
    const query = getRawSearchValue();

    event.preventDefault();
    if (query.length === 0) {
      return;
    }

    (browserWindow || window).open(
      `https://www.google.com/search?q=${encodeURIComponent(query)}`,
      "_blank"
    );
  });

  $("#search-container").on("input.dashboardFilters", "input[name=q]", () => {
    applyTileFilters();
  });

  $("#app").on("click.dashboardFilters", ".tag", (event) => {
    event.preventDefault();
    const tagButton = $(event.currentTarget);
    const tag = String(tagButton.data("tag") || ALL_TAG);
    $("#taglist .tag").removeClass("current");
    tagButton.addClass("current");

    if (tag !== ALL_TAG) {
      const usageHistory = getTagUsageHistory();
      usageHistory.push(tag);
      saveTagUsageHistory(usageHistory);
    }

    reorderTagButtonsByUsage();
    applyTileFilters();
  });

  applyTileFilters();

  return {
    applyTileFilters,
    getVisibleItems,
    updateTagButtonCounts,
  };
});
