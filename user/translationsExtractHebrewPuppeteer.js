const puppeteer = require('puppeteer');

(async () => {
    const filePath = process.argv[2];
    const browser = await puppeteer.launch();
    const page = await browser.newPage();
    await page.goto('file://' + filePath, { waitUntil: 'load' });

    const hebrewTexts = await page.evaluate(() => {
        const textNodes = [];
        const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, null);
        while (walker.nextNode()) {
            const text = walker.currentNode.nodeValue.trim();
            if (/[\u0590-\u05FF]/.test(text)) {
                textNodes.push(text);
            }
        }
        return textNodes;
    });

    console.log(JSON.stringify(hebrewTexts));
    await browser.close();
})();

function extractHebrewFromAttributes($node) {
    $hebrewStrings = [];
    $attributesToCheck = ['data-*', 'placeholder', 'aria-label', 'aria-describedby', 'style'];

    foreach ($attributesToCheck as $attr) {
        if ($node->hasAttribute($attr)) {
            $attrValue = $node->getAttribute($attr);

            // For styles, process separately
            if ($attr === 'style') {
                $hebrewStrings = array_merge($hebrewStrings, extractHebrewFromCSS($attrValue));
            } else {
                $hebrewStrings = array_merge($hebrewStrings, extractHebrewPhrases($attrValue));
            }
        }
    }

    return $hebrewStrings;
}
