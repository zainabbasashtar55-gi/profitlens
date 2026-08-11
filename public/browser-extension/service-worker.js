chrome.runtime.onInstalled.addListener(() => {
  chrome.contextMenus.create({
    id: "profitlens-log-expense",
    title: "Log as ProfitLens expense",
    contexts: ["selection"]
  });
});

chrome.contextMenus.onClicked.addListener(async (info, tab) => {
  if (info.menuItemId !== "profitlens-log-expense") return;

  const settings = await chrome.storage.sync.get(["profitlensBaseUrl", "profitlensToken"]);
  const amount = Number(String(info.selectionText || "").replace(/[^0-9.]/g, ""));

  if (!settings.profitlensBaseUrl || !settings.profitlensToken || Number.isNaN(amount)) return;

  await fetch(`${settings.profitlensBaseUrl.replace(/\/$/, "")}/api/v1/integrations/browser-extension/expense`, {
    method: "POST",
    headers: {
      "Authorization": `Bearer ${settings.profitlensToken}`,
      "Content-Type": "application/json",
      "Accept": "application/json"
    },
    body: JSON.stringify({
      amount,
      source_url: tab?.url,
      description: tab?.title || "Browser-captured expense"
    })
  });
});
