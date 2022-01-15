# Every Single Month
This is a simple little web app to allow users to input the name of a city, and it generates a meme for them showing much money their town would receive under [Andrew Yang's Freedom Dividend](https://www.yang2020.com/policies/the-freedom-dividend/).

## Requirements
 - A web server running PHP 7.0 or newer, with the `curl` module installed.
 - An account with [zenserp](https://zenserp.com/) if you want the Google Search results to work.
 - If you want the screenshot function to work, [install Puppeteer](https://developers.google.com/web/tools/puppeteer/get-started#installation).
    - Puppeteer has plenty of its own dependencies, including node and chromium, and some `x` drivers for headless systems.

## Installation
1. Clone the repository
2. Rename `config_sample.php` to `config.php` and update the settings inside
3. If you want screenshots to work, run `npm i puppeteer`
4. Run `index.php` in a web browser.

## Questions?
This project is in active development as of January 2020. If you have any questions, feel free to open a new issue and I'll do my best to respond.
