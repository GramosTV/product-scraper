const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
        viewport: { width: 1280, height: 720 },
    });
    const page = await context.newPage();
    await page.goto(process.argv[2], { waitUntil: 'networkidle', timeout: 20000 });
    // await page.waitForTimeout(5000); // sometimes Cloudflare waits for JS evaluation
    const html = await page.content();
    console.log(html);
    await browser.close();
})();
