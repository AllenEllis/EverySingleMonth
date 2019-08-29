const puppeteer = require('puppeteer');




(async () => {

    'use strict';
    const url = process.argv[2];
    const path = process.argv[3];

    const browser = await puppeteer.launch();
    //const browser = await puppeteer.launch({ executablePath: 'chrome.exe' });
    //const browser = await puppeteer.launch({
    //    args: ['--no-sandbox', '--disable-setuid-sandbox']
    //});
    //const browser = await puppeteer.launch({executablePath: '/usr/bin/chromium-browser'});
    const page = await browser.newPage();
    await page.setViewport({
        width: 1080,
        height: 1080,
        deviceScaleFactor: 1,
    });
    await page.goto(url);
    await page.screenshot({path: path});

    await browser.close();
})();
