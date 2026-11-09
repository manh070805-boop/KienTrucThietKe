import { defineConfig } from "@playwright/test";
export default defineConfig({
    testDir: "./tests/e2e",
    fullyParallel: false,
    workers: 1,
    timeout: 180000,
    expect: { timeout: 15000 },
    use: {
        baseURL: process.env.E2E_URL || "http://127.0.0.1:8001",
        channel: "chrome",
        trace: "retain-on-failure",
        screenshot: "only-on-failure",
    },
    reporter: [
        ["list"],
        ["json", { outputFile: "storage/app/e2e-results.json" }],
    ],
    outputDir: "storage/app/playwright-results",
});
