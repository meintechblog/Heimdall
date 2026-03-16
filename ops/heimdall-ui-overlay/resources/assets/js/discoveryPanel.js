function escapeHtml(value) {
  return String(value == null ? "" : value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function renderCandidateMarkup(candidate) {
  const subtitle = [candidate.host, candidate.subtitle]
    .filter(Boolean)
    .join(" · ");
  const safeCandidate = {
    id: escapeHtml(candidate.id),
    source: escapeHtml(candidate.source),
    url: escapeHtml(candidate.url),
    iconUrl: escapeHtml(candidate.iconUrl),
    sourceLabel: escapeHtml(candidate.sourceLabel),
    title: escapeHtml(candidate.title),
    subtitle: escapeHtml(subtitle),
  };

  return `
    <article
      class="discovery-candidate"
      data-candidate-id="${safeCandidate.id}"
      data-source="${safeCandidate.source}"
      data-url="${safeCandidate.url}"
    >
      <span class="discovery-candidate-card">
        <button
          type="button"
          class="discovery-candidate-open"
          aria-label="${safeCandidate.title} &ouml;ffnen"
        >
          <span class="app-icon-container">
            <img class="app-icon" src="${safeCandidate.iconUrl}" alt="${safeCandidate.sourceLabel}" />
            <span class="tile-icon-loading-overlay is-hidden" aria-hidden="true">
              <span class="tile-icon-loading-visual">
                <span class="tile-icon-loading-spinner tile-icon-loading-spinner-ring" aria-hidden="true"></span>
              </span>
            </span>
          </span>
          <span class="discovery-candidate-details">
            <span class="discovery-candidate-source">${safeCandidate.sourceLabel}</span>
            <span class="discovery-candidate-title">${safeCandidate.title}</span>
            <span class="discovery-candidate-meta">${safeCandidate.subtitle}</span>
          </span>
        </button>
        <button
          type="button"
          class="discovery-candidate-add"
          aria-label="${safeCandidate.title} hinzuf&uuml;gen"
        >
          Hinzuf&uuml;gen
        </button>
      </span>
    </article>
  `;
}

function initHeimdallDiscoveryPanel(options = {}) {
  const doc =
    options.document || (typeof document !== "undefined" ? document : null);
  const win = options.window || (typeof window !== "undefined" ? window : null);
  const fetchImpl =
    options.fetch ||
    (win && typeof win.fetch === "function" ? win.fetch.bind(win) : null);
  const scheduleInterval =
    options.scheduleInterval ||
    (win && typeof win.setInterval === "function"
      ? win.setInterval.bind(win)
      : null);
  const scheduleTimeout =
    options.scheduleTimeout ||
    (win && typeof win.setTimeout === "function"
      ? win.setTimeout.bind(win)
      : null);
  const clearScheduledTimeout =
    options.clearScheduledTimeout ||
    (win && typeof win.clearTimeout === "function"
      ? win.clearTimeout.bind(win)
      : null);
  const autoStart = options.autoStart !== false;

  if (!doc || !fetchImpl) {
    return null;
  }

  const hub = doc.getElementById("discovery-hub");

  if (!hub) {
    return null;
  }

  const shell = hub.closest(".search-discovery-shell") || hub.parentElement;

  const summaryUrl = hub.getAttribute("data-summary-url");
  const progressUrl = hub.getAttribute("data-progress-url");
  const candidatesUrl = hub.getAttribute("data-candidates-url");
  const addUrl = hub.getAttribute("data-add-url");
  const refreshSeconds = Number(
    hub.getAttribute("data-refresh-seconds") || 300
  );

  const toggle = hub.querySelector("#discovery-toggle");
  const countLabel = hub.querySelector('[data-role="count"]');
  const iconLabel = hub.querySelector('[data-role="icon"]');
  const panel = shell ? shell.querySelector('[data-role="panel"]') : null;
  const state = shell ? shell.querySelector('[data-role="state"]') : null;
  const candidatesContainer = shell
    ? shell.querySelector('[data-role="candidates"]')
    : null;

  if (
    !toggle ||
    !countLabel ||
    !iconLabel ||
    !panel ||
    !state ||
    !candidatesContainer
  ) {
    return null;
  }

  let progressTimer = null;
  let progressRunId = 0;

  function setState(message = "", hidden = false) {
    state.textContent = message;
    state.classList.toggle("is-hidden", hidden || message === "");
  }

  function setExpanded(expanded) {
    toggle.setAttribute("aria-expanded", expanded ? "true" : "false");
    iconLabel.textContent = expanded ? "-" : "+";
    panel.classList.toggle("is-hidden", !expanded);

    if (
      expanded !== true &&
      countLabel.textContent.trim() === "" &&
      candidatesContainer.children.length === 0
    ) {
      toggle.classList.add("is-hidden");
    }
  }

  function stopProgressPolling() {
    progressRunId += 1;

    if (progressTimer !== null && clearScheduledTimeout) {
      clearScheduledTimeout(progressTimer);
    }

    progressTimer = null;
  }

  function setCount(totalCount, keepVisible = false) {
    if (totalCount > 0) {
      toggle.classList.remove("is-hidden");
      countLabel.textContent = String(totalCount);
      return;
    }

    if (keepVisible) {
      toggle.classList.remove("is-hidden");
      countLabel.textContent = "";
      return;
    }

    toggle.classList.add("is-hidden");
    countLabel.textContent = "";
    candidatesContainer.innerHTML = "";
    setExpanded(false);
    setState("", true);
  }

  async function refreshSummary() {
    const response = await fetchImpl(summaryUrl, {
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    if (!response.ok) {
      throw new Error(
        `Failed to refresh discovery summary: ${response.status}`
      );
    }

    const payload = await response.json();
    setCount(Number(payload.totalCount || 0));

    return payload;
  }

  async function loadCandidates() {
    setState("Suche nach neuen Services ...");

    const response = await fetchImpl(candidatesUrl, {
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    if (!response.ok) {
      throw new Error(`Failed to load candidates: ${response.status}`);
    }

    const payload = await response.json();
    const candidates = Array.isArray(payload.candidates)
      ? payload.candidates
      : [];

    candidatesContainer.innerHTML = candidates
      .map(renderCandidateMarkup)
      .join("");
    setCount(
      Number(payload.totalCount || candidates.length),
      payload.isComplete !== true
    );

    if (candidates.length > 0) {
      setState("", true);
    } else {
      setState("Keine neuen Services verfügbar.");
    }

    return payload;
  }

  function renderCandidates(payload) {
    const candidates = Array.isArray(payload.candidates)
      ? payload.candidates
      : [];

    candidatesContainer.innerHTML = candidates
      .map(renderCandidateMarkup)
      .join("");
    setCount(Number(payload.totalCount || candidates.length));

    if (payload.isComplete === true) {
      if (candidates.length > 0) {
        setState("", true);
      } else {
        setState("Keine neuen Services verfügbar.");
      }

      return;
    }

    const completedSources = Number(payload.completedSources || 0);
    const totalSources = Number(payload.totalSources || 0);
    const progressLabel =
      totalSources > 0
        ? `Suche nach neuen Services ... (${completedSources}/${totalSources})`
        : "Suche nach neuen Services ...";

    setState(progressLabel);
  }

  async function loadProgressiveCandidates(fresh = false, runId = 0) {
    if (!progressUrl) {
      return loadCandidates();
    }

    const requestUrl = fresh ? `${progressUrl}?fresh=1` : progressUrl;
    const response = await fetchImpl(requestUrl, {
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    if (!response.ok) {
      throw new Error(
        `Failed to load progressive candidates: ${response.status}`
      );
    }

    const payload = await response.json();

    if (runId !== progressRunId) {
      return payload;
    }

    renderCandidates(payload);

    if (
      payload.isComplete !== true &&
      toggle.getAttribute("aria-expanded") === "true" &&
      doc.hidden !== true &&
      scheduleTimeout
    ) {
      progressTimer = scheduleTimeout(() => {
        loadProgressiveCandidates(false, runId).catch(() => {
          setState(
            "Die laufende Suche konnte gerade nicht aktualisiert werden."
          );
          stopProgressPolling();
        });
      }, 700);
    }

    return payload;
  }

  async function startProgressiveDiscovery() {
    stopProgressPolling();
    setState("Suche nach neuen Services ...");
    const runId = progressRunId;
    return loadProgressiveCandidates(true, runId);
  }

  async function addCandidate(button) {
    const candidateButton = button;

    if (candidateButton.classList.contains("is-adding")) {
      return;
    }

    candidateButton.classList.add("is-adding");

    const overlay = candidateButton.querySelector(".tile-icon-loading-overlay");
    const addAction = candidateButton.querySelector(".discovery-candidate-add");

    if (overlay) {
      overlay.classList.remove("is-hidden");
    }

    if (addAction) {
      addAction.disabled = true;
    }

    try {
      const response = await fetchImpl(addUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify({
          source: candidateButton.getAttribute("data-source"),
          candidateId: candidateButton.getAttribute("data-candidate-id"),
        }),
      });

      if (!response.ok) {
        throw new Error(`Failed to add candidate: ${response.status}`);
      }

      await response.json();

      candidateButton.remove();

      const remainingCandidates = candidatesContainer.querySelectorAll(
        ".discovery-candidate"
      ).length;
      const currentCount = Number(countLabel.textContent || 0);
      const nextCount =
        Number.isFinite(currentCount) && currentCount > 0
          ? Math.max(remainingCandidates, currentCount - 1)
          : remainingCandidates;

      if (remainingCandidates > 0) {
        setCount(nextCount, toggle.getAttribute("aria-expanded") === "true");
        setState("", true);
      } else {
        setCount(0, false);
      }
    } catch (error) {
      setState("Der Eintrag konnte gerade nicht übernommen werden.");
      candidateButton.classList.remove("is-adding");

      if (overlay) {
        overlay.classList.add("is-hidden");
      }

      if (addAction) {
        addAction.disabled = false;
      }

      throw error;
    }
  }

  function openCandidate(candidateElement) {
    const url = candidateElement.getAttribute("data-url");

    if (!url) {
      return;
    }

    if (win && typeof win.open === "function") {
      win.open(url, "_blank");
    }
  }

  toggle.addEventListener("click", async (event) => {
    event.preventDefault();

    if (toggle.classList.contains("is-hidden")) {
      return;
    }

    if (toggle.getAttribute("aria-expanded") === "true") {
      stopProgressPolling();
      setExpanded(false);
      return;
    }

    setExpanded(true);
    await loadCandidates();
  });

  shell.addEventListener("click", (event) => {
    const addButton = event.target.closest(".discovery-candidate-add");

    if (addButton) {
      const candidate = addButton.closest(".discovery-candidate");

      if (!candidate) {
        return;
      }

      event.preventDefault();
      addCandidate(candidate).catch(() => {});
      return;
    }

    const openButton = event.target.closest(".discovery-candidate-open");

    if (!openButton) {
      return;
    }

    const candidate = openButton.closest(".discovery-candidate");

    if (!candidate || candidate.classList.contains("is-adding")) {
      return;
    }

    event.preventDefault();
    openCandidate(candidate);
  });

  doc.addEventListener("visibilitychange", () => {
    if (doc.hidden === true) {
      stopProgressPolling();
      return;
    }

    if (toggle.getAttribute("aria-expanded") === "true") {
      stopProgressPolling();
      loadCandidates().catch(() => {});
    } else {
      refreshSummary().catch(() => {});
    }
  });

  if (scheduleInterval && refreshSeconds > 0) {
    scheduleInterval(() => {
      if (doc.hidden === true) {
        return;
      }

      refreshSummary().catch(() => {});
    }, refreshSeconds * 1000);
  }

  if (autoStart) {
    refreshSummary().catch(() => {});
  }

  return {
    refreshSummary,
    loadCandidates,
    startProgressiveDiscovery,
    setExpanded,
  };
}

if (typeof window !== "undefined") {
  window.initHeimdallDiscoveryPanel = initHeimdallDiscoveryPanel;
}

if (typeof module === "object" && module.exports) {
  module.exports = initHeimdallDiscoveryPanel;
}
