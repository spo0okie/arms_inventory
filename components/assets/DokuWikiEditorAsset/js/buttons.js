// 1. Имитируем глобальные переменные DokuWiki
var toolbar = [{
    "type": "format",
    "title": "Полужирный",
    "icon": "bold.png",
    "key": "b",
    "open": "**",
    "close": "**",
    "block": false
}, {
    "type": "format",
    "title": "Курсив",
    "icon": "italic.png",
    "key": "i",
    "open": "//",
    "close": "//",
    "block": false
}, {
    "type": "format",
    "title": "Подчёркнутый",
    "icon": "underline.png",
    "key": "u",
    "open": "__",
    "close": "__",
    "block": false
}, {
    "type": "format",
    "title": "Текст кода",
    "icon": "mono.png",
    "key": "m",
    "open": "''",
    "close": "''",
    "block": false
}, {
    "type": "format",
    "title": "Зачёркнутый",
    "icon": "strike.png",
    "key": "d",
    "open": "<del>",
    "close": "</del>",
    "block": false
}, {
    "type": "autohead",
    "title": "Заголовок текущего уровня",
    "icon": "hequal.png",
    "key": "8",
    "text": "Заголовок",
    "mod": 0,
    "block": true
}, {
    "type": "autohead",
    "title": "Заголовок меньшего уровня (подзаголовок)",
    "icon": "hminus.png",
    "key": "9",
    "text": "Заголовок",
    "mod": 1,
    "block": true
}, {
    "type": "autohead",
    "title": "Заголовок большего уровня",
    "icon": "hplus.png",
    "key": "0",
    "text": "Заголовок",
    "mod": -1,
    "block": true
}, {
    "type": "picker",
    "title": "Выбор заголовка",
    "icon": "h.png",
    "class": "pk_hl",
    "list": [{
        "type": "format",
        "title": "Заголовок 1-го уровня",
        "icon": "h1.png",
        "key": "1",
        "open": "====== ",
        "close": " ======\n"
    }, {
        "type": "format",
        "title": "Заголовок 2-го уровня",
        "icon": "h2.png",
        "key": "2",
        "open": "===== ",
        "close": " =====\n"
    }, {
        "type": "format",
        "title": "Заголовок 3-го уровня",
        "icon": "h3.png",
        "key": "3",
        "open": "==== ",
        "close": " ====\n"
    }, {
        "type": "format",
        "title": "Заголовок 4-го уровня",
        "icon": "h4.png",
        "key": "4",
        "open": "=== ",
        "close": " ===\n"
    }, {
        "type": "format",
        "title": "Заголовок 5-го уровня",
        "icon": "h5.png",
        "key": "5",
        "open": "== ",
        "close": " ==\n"
    }
    ],
    "block": true
}, {
    "type": "linkwiz",
    "title": "Внутренняя ссылка",
    "icon": "link.png",
    "key": "l",
    "open": "[[",
    "close": "]]",
    "block": false
}, {
    "type": "format",
    "title": "Внешняя ссылка",
    "icon": "linkextern.png",
    "open": "[[",
    "close": "]]",
    "sample": "http://example.com|Внешняя ссылка",
    "block": false
}, {
    "type": "formatln",
    "title": "Элемент нумерованного списка",
    "icon": "ol.png",
    "open": "  - ",
    "close": "",
    "key": "-",
    "block": true
}, {
    "type": "formatln",
    "title": "Элемент ненумерованного списка",
    "icon": "ul.png",
    "open": "  * ",
    "close": "",
    "key": ".",
    "block": true
}, {
    "type": "insert",
    "title": "Горизонтальная линия",
    "icon": "hr.png",
    "insert": "\n----\n",
    "block": true
}, {
    "type": "picker",
    "title": "Смайлики",
    "icon": "smiley.png",
    "list": {
        "8-)": "icon_cool.gif",
        "8-O": "icon_eek.gif",
        "8-o": "icon_eek.gif",
        ":-(": "icon_sad.gif",
        ":-)": "icon_smile.gif",
        "=)": "icon_smile2.gif",
        ":-/": "icon_doubt.gif",
        ":-\\": "icon_doubt2.gif",
        ":-?": "icon_confused.gif",
        ":-D": "icon_biggrin.gif",
        ":-P": "icon_razz.gif",
        ":-o": "icon_surprised.gif",
        ":-O": "icon_surprised.gif",
        ":-x": "icon_silenced.gif",
        ":-X": "icon_silenced.gif",
        ":-|": "icon_neutral.gif",
        ";-)": "icon_wink.gif",
        "m(": "facepalm.gif",
        "^_^": "icon_fun.gif",
        ":?:": "icon_question.gif",
        ":!:": "icon_exclaim.gif",
        "LOL": "icon_lol.gif",
        "FIXME": "fixme.gif",
        "DELETEME": "delete.gif"
    },
    "icobase": "smileys",
    "block": false
}, {
    "type": "picker",
    "title": "Специальные символы",
    "icon": "chars.png",
    "list": ["À", "à", "Á", "á", "Â", "â", "Ã", "ã", "Ä", "ä", "Ǎ", "ǎ", "Ă", "ă", "Å", "å", "Ā", "ā", "Ą", "ą", "Æ", "æ", "Ć", "ć", "Ç", "ç", "Č", "č", "Ĉ", "ĉ", "Ċ", "ċ", "Ð", "đ", "ð", "Ď", "ď", "È", "è", "É", "é", "Ê", "ê", "Ë", "ë", "Ě", "ě", "Ē", "ē", "Ė", "ė", "Ę", "ę", "Ģ", "ģ", "Ĝ", "ĝ", "Ğ", "ğ", "Ġ", "ġ", "Ĥ", "ĥ", "Ì", "ì", "Í", "í", "Î", "î", "Ï", "ï", "Ǐ", "ǐ", "Ī", "ī", "İ", "ı", "Į", "į", "Ĵ", "ĵ", "Ķ", "ķ", "Ĺ", "ĺ", "Ļ", "ļ", "Ľ", "ľ", "Ł", "ł", "Ŀ", "ŀ", "Ń", "ń", "Ñ", "ñ", "Ņ", "ņ", "Ň", "ň", "Ò", "ò", "Ó", "ó", "Ô", "ô", "Õ", "õ", "Ö", "ö", "Ǒ", "ǒ", "Ō", "ō", "Ő", "ő", "Œ", "œ", "Ø", "ø", "Ŕ", "ŕ", "Ŗ", "ŗ", "Ř", "ř", "Ś", "ś", "Ş", "ş", "Š", "š", "Ŝ", "ŝ", "Ţ", "ţ", "Ť", "ť", "Ù", "ù", "Ú", "ú", "Û", "û", "Ü", "ü", "Ǔ", "ǔ", "Ū", "ū", "Ů", "ů", "Ų", "ų", "Ű", "ű", "Ŵ", "ŵ", "Ý", "ý", "Ÿ", "ÿ", "Ŷ", "ŷ", "Ź", "ź", "Ż", "ż", "Ž", "ž", "Þ", "þ", "ß", "Ħ", "ħ", "¿", "¡", "¢", "£", "¤", "¥", "€", "¦", "§", "ª", "¬", "¯", "°", "±", "÷", "‰", "¼", "½", "¾", "¹", "²", "³", "µ", "¶", "†", "‡", "·", "•", "º", "∀", "∂", "∃", "Ə", "ə", "∅", "∇", "∈", "∉", "∋", "∏", "∑", "‾", "−", "∗", "×", "⁄", "√", "∝", "∞", "∠", "∧", "∨", "∩", "∪", "∫", "∴", "∼", "≅", "≈", "≠", "≡", "≤", "≥", "⊂", "⊃", "⊄", "⊆", "⊇", "⊕", "⊗", "⊥", "⋅", "◊", "℘", "ℑ", "ℜ", "ℵ", "♠", "♣", "♥", "♦", "α", "β", "Γ", "γ", "Δ", "δ", "ε", "ζ", "η", "Θ", "θ", "ι", "κ", "Λ", "λ", "μ", "Ξ", "ξ", "Π", "π", "ρ", "Σ", "σ", "Τ", "τ", "υ", "Φ", "φ", "χ", "Ψ", "ψ", "Ω", "ω", "★", "☆", "☎", "☚", "☛", "☜", "☝", "☞", "☟", "☹", "☺", "✔", "✘", "„", "“", "”", "‚", "‘", "’", "«", "»", "‹", "›", "—", "–", "…", "←", "↑", "→", "↓", "↔", "⇐", "⇑", "⇒", "⇓", "⇔", "©", "™", "®", "′", "″", "[", "]", "{", "}", "~", "(", ")", "%", "§", "$", "#", "|", "@"],
    "block": false
}, {
    "type": "signature",
    "title": "Вставить подпись",
    "icon": "sig.png",
    "key": "y",
    "block": false
}, {
    "type": "picker",
    "title": "Wrap",
    "icon": "../../plugins/wrap/images/toolbar/picker.png",
    "list": [{
        "type": "format",
        "title": "колонки",
        "icon": "../../plugins/wrap/images/toolbar/column.png",
        "open": "<WRAP group>\n<WRAP half column>\n",
        "close": "\n</WRAP>\n\n<WRAP half column>\n\n</WRAP>\n</WRAP>\n"
    }, {
        "type": "format",
        "title": "простой центрированный блок",
        "icon": "../../plugins/wrap/images/toolbar/box.png",
        "open": "<WRAP center round box 60%>\n",
        "close": "\n</WRAP>\n"
    }, {
        "type": "format",
        "title": "блок «Информация»",
        "icon": "../../plugins/wrap/images/note/16/info.png",
        "open": "<WRAP center round info 60%>\n",
        "close": "\n</WRAP>\n"
    }, {
        "type": "format",
        "title": "блок «Подсказка»",
        "icon": "../../plugins/wrap/images/note/16/tip.png",
        "open": "<WRAP center round tip 60%>\n",
        "close": "\n</WRAP>\n"
    }, {
        "type": "format",
        "title": "блок «Важно»",
        "icon": "../../plugins/wrap/images/note/16/important.png",
        "open": "<WRAP center round important 60%>\n",
        "close": "\n</WRAP>\n"
    }, {
        "type": "format",
        "title": "блок «Тревога»",
        "icon": "../../plugins/wrap/images/note/16/alert.png",
        "open": "<WRAP center round alert 60%>\n",
        "close": "\n</WRAP>\n"
    }, {
        "type": "format",
        "title": "блок «Справка»",
        "icon": "../../plugins/wrap/images/note/16/help.png",
        "open": "<WRAP center round help 60%>\n",
        "close": "\n</WRAP>\n"
    }, {
        "type": "format",
        "title": "блок «Скачивание»",
        "icon": "../../plugins/wrap/images/note/16/download.png",
        "open": "<WRAP center round download 60%>\n",
        "close": "\n</WRAP>\n"
    }, {
        "type": "format",
        "title": "блок «Список задач»",
        "icon": "../../plugins/wrap/images/note/16/todo.png",
        "open": "<WRAP center round todo 60%>\n",
        "close": "\n</WRAP>\n"
    }, {
        "type": "insert",
        "title": "очистить float’ы",
        "icon": "../../plugins/wrap/images/toolbar/clear.png",
        "insert": "<WRAP clear/>\n"
    }, {
        "type": "format",
        "title": "пометить важным",
        "icon": "../../plugins/wrap/images/toolbar/em.png",
        "open": "<wrap em>",
        "close": "</wrap>"
    }, {
        "type": "format",
        "title": "выделить (маркер)",
        "icon": "../../plugins/wrap/images/toolbar/hi.png",
        "open": "<wrap hi>",
        "close": "</wrap>"
    }, {
        "type": "format",
        "title": "пометить неважным",
        "icon": "../../plugins/wrap/images/toolbar/lo.png",
        "open": "<wrap lo>",
        "close": "</wrap>"
    }
    ]
}];

