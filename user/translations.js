let translations = {};
const rtlLanguages = ['he', 'ar', 'fa', 'ur'];

function safeExecute(description, fn) {
    try { fn(); } catch (error) {
        console.error(`Error during ${description}:`, error);
    }
}

fetch('translations.json')
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok ' + response.statusText);
        }
        return response.json();
    })
    .then(data => {
        translations = data;
        console.log("Translations loaded successfully.");

        const languagePicker = document.getElementById('language-picker');
        const currentLang = languagePicker ? languagePicker.value : 'he';

        safeExecute("initial translation", () => {
            translatePage(currentLang);
        });

        const holder = document.querySelector('.holder');
        if (holder) {
            holder.style.display = 'none';
        }

        document.documentElement.style.visibility = 'visible';
        document.body.style.visibility = 'visible';
        console.log("Page is now visible.");

        // Observe the DOM for newly added nodes (pop-ups might appear this way)
        observeNewNodes();

    })
    .catch(error => {
        console.error('Error loading translations:', error);
        const holder = document.querySelector('.holder');
        if (holder) {
            holder.style.display = 'none';
        }
        document.documentElement.style.visibility = 'visible';
        document.body.style.visibility = 'visible';
        console.log("Page is now visible despite errors.");
    });

function setDirection(lang) {
    try {
        if (rtlLanguages.includes(lang)) {
            document.documentElement.setAttribute('dir', 'rtl');
        } else {
            document.documentElement.setAttribute('dir', 'ltr');
        }
    } catch (error) {
        console.error("Error in setDirection:", error);
    }
}

function normalizeText(str) {
    return str.replace(/[0-9\!\@\#\$\%\^\&\*\(\)\[\]\{\}\+\=\_\-\.\,\:\;\"\'\/\\\|<>\?\~`₪]/g, '').trim();
}

function findTranslation(originalText, lang) {
    const trimmed = originalText.trim();
    if (translations[lang] && translations[lang][trimmed]) {
        return translations[lang][trimmed];
    }
    const normalized = normalizeText(trimmed);
    if (!normalized) return null;

    const dictKeys = Object.keys(translations[lang] || {});
    for (const key of dictKeys) {
        if (normalizeText(key) === normalized) {
            return translations[lang][key];
        }
    }
    return null;
}

function translateElement(element, lang) {
    safeExecute("translateElement text nodes", () => {
        const textNodes = Array.from(element.childNodes).filter(node => node.nodeType === Node.TEXT_NODE);
        for (let node of textNodes) {
            const originalText = node.textContent;
            const translated = findTranslation(originalText, lang);
            if (translated && translated !== originalText) node.textContent = translated;
        }
    });

    safeExecute("translateAttributes", () => translateAttributes(element, lang));
    safeExecute("translateAdditionalAttributes", () => translateAdditionalAttributes(element, lang));
    safeExecute("translateSelectOptions", () => translateSelectOptions(element, lang));
    safeExecute("translateSVGText", () => {
        if (element instanceof SVGElement) translateSVGText(element, lang);
    });
    safeExecute("translateSpecificElements", () => translateSpecificElements(element, lang));
    safeExecute("translateTextareaPlaceholders", () => translateTextareaPlaceholders(element, lang));
    safeExecute("translateTooltips", () => translateTooltips(element, lang));
    safeExecute("translateInlineEventHandlers", () => translateInlineEventHandlers(element, lang));
}

function translateAttributes(element, lang) {
    try {
        const attributes = ['placeholder', 'title', 'aria-label', 'value'];
        for (let attr of attributes) {
            if (element.hasAttribute(attr)) {
                const original = element.getAttribute(attr).trim();
                const translated = findTranslation(original, lang);
                if (translated && translated !== original) {
                    if (attr === 'value' && ['INPUT','BUTTON','SELECT'].includes(element.tagName)) {
                        element.value = translated;
                    } else {
                        element.setAttribute(attr, translated);
                    }
                }
            }
        }
    } catch (error) {
        console.error("Error in translateAttributes:", error);
    }
}

function translateAdditionalAttributes(element, lang) {
    try {
        if (element.hasAttribute('alt')) {
            const originalAlt = element.getAttribute('alt').trim();
            const translatedAlt = findTranslation(originalAlt, lang);
            if (translatedAlt && translatedAlt !== originalAlt) {
                element.setAttribute('alt', translatedAlt);
            }
        }

        Array.from(element.attributes).forEach(attr => {
            if (attr.name.startsWith('data-') && !['data-phone', 'data-sms', 'data-mail'].includes(attr.name)) {
                const originalData = attr.value.trim();
                const translatedData = findTranslation(originalData, lang);
                if (translatedData && translatedData !== originalData) {
                    element.setAttribute(attr.name, translatedData);
                }
            }
        });
    } catch (error) {
        console.error("Error in translateAdditionalAttributes:", error);
    }
}

function translateSelectOptions(element, lang) {
    try {
        if (element.tagName.toLowerCase() === 'select') {
            const options = element.querySelectorAll('option');
            options.forEach(option => {
                const originalText = option.textContent.trim();
                const translatedText = findTranslation(originalText, lang);
                if (translatedText && translatedText !== originalText) {
                    option.textContent = translatedText;
                }
            });
        }
    } catch (error) {
        console.error("Error in translateSelectOptions:", error);
    }
}

function translateSVGText(svgElement, lang) {
    try {
        const textElements = svgElement.querySelectorAll('text');
        textElements.forEach(textEl => {
            const originalText = textEl.textContent.trim();
            const translatedText = findTranslation(originalText, lang);
            if (translatedText && translatedText !== originalText) {
                textEl.textContent = translatedText;
            }
        });
    } catch (error) {
        console.error("Error in translateSVGText:", error);
    }
}

function translateSpecificElements(element, lang) {
    try {
        if (element.classList.contains('translate')) {
            const originalText = element.textContent.trim();
            const translatedText = findTranslation(originalText, lang);
            if (translatedText && translatedText !== originalText) {
                element.textContent = translatedText;
            }
        }

        if (element.hasAttribute('data-translate')) {
            const originalText = element.getAttribute('data-translate').trim();
            const translatedText = findTranslation(originalText, lang);
            if (translatedText && translatedText !== originalText) {
                element.textContent = translatedText;
            }
        }
    } catch (error) {
        console.error("Error in translateSpecificElements:", error);
    }
}

function translateTextareaPlaceholders(element, lang) {
    try {
        if (element.tagName.toLowerCase() === 'textarea' && element.hasAttribute('placeholder')) {
            const originalPlaceholder = element.getAttribute('placeholder').trim();
            const translatedPlaceholder = findTranslation(originalPlaceholder, lang);
            if (translatedPlaceholder && translatedPlaceholder !== originalPlaceholder) {
                element.setAttribute('placeholder', translatedPlaceholder);
            }
        }
    } catch (error) {
        console.error("Error in translateTextareaPlaceholders:", error);
    }
}

function translateTooltips(element, lang) {
    try {
        const tooltipAttributes = ['data-tooltip', 'title'];
        tooltipAttributes.forEach(attr => {
            if (element.hasAttribute(attr)) {
                const originalTooltip = element.getAttribute(attr).trim();
                const translatedTooltip = findTranslation(originalTooltip, lang);
                if (translatedTooltip && translatedTooltip !== originalTooltip) {
                    element.setAttribute(attr, translatedTooltip);
                }
            }
        });
    } catch (error) {
        console.error("Error in translateTooltips:", error);
    }
}

function translateInlineEventHandlers(element, lang) {
    // Typically these contain code rather than text.
}

function translateAllElements(lang) {
    try {
        const allElements = document.querySelectorAll('*');
        allElements.forEach(element => translateElement(element, lang));
    } catch (error) {
        console.error("Error in translateAllElements:", error);
    }
}

function translatePage(lang) {
    try {
        if (!translations[lang]) {
            console.warn(`No translations for language: ${lang}`);
        }
        setDirection(lang);
        translateAllElements(lang);
        console.log(`Page translation to ${lang} completed successfully.`);
    } catch (error) {
        console.error("Error in translatePage:", error);
    }
}

function translateAllElementsForNode(node, lang) {
    safeExecute("translate node and descendants", () => {
        translateElement(node, lang);
        const descendants = node.querySelectorAll('*');
        descendants.forEach(child => translateElement(child, lang));
    });
    console.log("All elements inside the container have been translated.");
}

function observeNewNodes() {
    const languagePicker = document.getElementById('language-picker');
    const currentLang = languagePicker ? languagePicker.value : 'he';

    const observer = new MutationObserver((mutationsList) => {
        for (const mutation of mutationsList) {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    // Check if this node or its descendants contain a .container
                    const containers = node.matches('.container') ? [node] : node.querySelectorAll('.container');
                    if (containers.length > 0) {
                        // We have at least one .container
                        containers.forEach(container => {
                            console.log("A .container appeared in the DOM:", container);

                            // Check if already translated
                            const translatedLang = container.getAttribute('data-translated-lang');
                            if (translatedLang !== currentLang) {
                                // Translate now
                                translateAllElementsForNode(container, currentLang);
                                container.setAttribute('data-translated-lang', currentLang);
                                console.log("Container translated to", currentLang, container);
                            } else {
                                console.log("Container already translated to current language:", currentLang);
                            }
                        });
                    }
                }
            });
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });
}

document.addEventListener("DOMContentLoaded", function() {
    safeExecute("language picker initialization", () => {
        const languagePicker = document.getElementById('language-picker');
        if (languagePicker && typeof $().select2 === 'function') {
            $(languagePicker).select2({ minimumResultsForSearch: -1 });
            languagePicker.addEventListener('change', function() {
                const selectedLang = this.value;
                translatePage(selectedLang);
            });
        }
    });
});
