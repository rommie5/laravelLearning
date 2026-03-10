const puppeteer = require('puppeteer');

(async () => {
    const browser = await puppeteer.launch();
    const page = await browser.newPage();

    page.on('console', msg => console.log('BROWSER_LOG:', msg.type(), msg.text()));
    page.on('pageerror', error => console.error('BROWSER_ERROR:', error.message));

    console.log("Navigating to login...");
    await page.goto('http://127.0.0.1:8000/login');

    await page.type('#email', 'officer@cms.com');
    await page.type('#password', 'password');

    console.log("Logging in...");
    await Promise.all([
        page.click('button[type="submit"]'),
        page.waitForNavigation()
    ]);

    console.log('Login complete, URL:', page.url());
    await new Promise(r => setTimeout(r, 2000));

    console.log("Navigating to /contracts...");
    await page.goto('http://127.0.0.1:8000/contracts');
    await new Promise(r => setTimeout(r, 2000));
    console.log('Contracts page URL:', page.url());

    console.log("Navigating to /contracts/create...");
    await page.goto('http://127.0.0.1:8000/contracts/create');
    await new Promise(r => setTimeout(r, 2000));

    await browser.close();
})().catch(e => {
    console.error('SCRIPT_ERROR:', e);
    process.exit(1);
});
