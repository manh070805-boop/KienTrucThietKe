import { chromium } from "@playwright/test";
const browser = await chromium.launch({ channel: "chrome" });
try {
    const page = await browser.newPage();
    const errors = [];
    page.on("pageerror", e => errors.push(e.message));
    await page.goto("http://127.0.0.1:8000");
    await page.getByRole("heading", { name: "Đăng nhập", exact: true }).waitFor({ timeout: 30000 });
    const preamble = await page.evaluate(() => window.__vite_plugin_react_preamble_installed__);
    if (!preamble || errors.length) throw new Error(JSON.stringify({ preamble, errors }));
    console.log("HMR smoke đạt: trang React render, preamble được cài, không có pageerror.");
} finally { await browser.close(); }
