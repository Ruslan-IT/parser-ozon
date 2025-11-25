const express = require("express");
const cors = require("cors");
const { chromium } = require("playwright");

const app = express();
app.use(cors());
app.use(express.json());

app.get("/parse", async (req, res) => {
    const url = req.query.url;
    if (!url) return res.status(400).json({ error: "Missing url parameter" });

    let browser;

    try {
        browser = await chromium.launch({
            headless: true,
            args: [
                "--disable-blink-features=AutomationControlled",
                "--no-sandbox",
                "--disable-dev-shm-usage"
            ]
        });

        const page = await browser.newPage();

        await page.setExtraHTTPHeaders({
            "User-Agent":
                "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36",
            "Accept-Language": "ru-RU,ru;q=0.9",
        });

        let composerData = null;

        // Перехват JSON ответа
        page.on("response", async (response) => {
            const rUrl = response.url();
            if (rUrl.includes("composer-api.bx/page/json")) {
                try {
                    composerData = await response.json();
                } catch {}
            }
        });

        console.log("➡ Открываем:", url);
        await page.goto(url, { waitUntil: "domcontentloaded" });

        // Имитируем поведение человека
        console.log("▶ Имитация скролла");

        for (let i = 0; i < 10; i++) {
            await page.mouse.wheel(0, 800);
            await page.waitForTimeout(300);
        }

        await page.waitForTimeout(600);

        // Скроллим вверх
        await page.mouse.wheel(0, -1500);
        await page.waitForTimeout(600);

        // Клик в любое место страницы
        await page.mouse.click(200, 200);
        await page.waitForTimeout(800);

        console.log("▶ Ожидание API...");
        for (let i = 0; i < 70; i++) {
            if (composerData) break;
            await page.waitForTimeout(120);
        }

        await browser.close();

        if (!composerData) {
            return res.json({
                status: "error",
                message: "Still no composer API (page blocking?)"
            });
        }

        return res.json({
            status: "success",
            data: composerData
        });
    } catch (e) {
        console.error(e);
        if (browser) await browser.close();
        return res.status(500).json({ error: e.toString() });
    }
});

app.listen(3001, () => {
    console.log("🔥 Ozon proxy running at http://localhost:3001");
});
