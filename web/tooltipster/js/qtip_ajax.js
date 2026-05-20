/**

 *

 *  Base64 encode / decode

 *  http://www.webtoolkit.info/

 **/
if (typeof Base64 === "undefined") {
	Base64 = {
		// private property
		_keyStr:
			"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",

		// public method for encoding
		encode: function (input) {
			let output = "";
			let chr1, chr2, chr3, enc1, enc2, enc3, enc4;
			let i = 0;

			input = Base64._utf8_encode(input);

			while (i < input.length) {
				chr1 = input.charCodeAt(i++);
				chr2 = input.charCodeAt(i++);
				chr3 = input.charCodeAt(i++);

				enc1 = chr1 >> 2;
				enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
				enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
				enc4 = chr3 & 63;

				if (isNaN(chr2)) {
					enc3 = enc4 = 64;
				} else if (isNaN(chr3)) {
					enc4 = 64;
				}

				output =
					output +
					this._keyStr.charAt(enc1) +
					this._keyStr.charAt(enc2) +
					this._keyStr.charAt(enc3) +
					this._keyStr.charAt(enc4);
			}

			return output;
		},

		// public method for decoding
		decode: function (input) {
			let output = "";
			let chr1, chr2, chr3;
			let enc1, enc2, enc3, enc4;
			let i = 0;

			input = input.replace(/[^A-Za-z0-9+\/=]/g, "");

			while (i < input.length) {
				enc1 = this._keyStr.indexOf(input.charAt(i++));
				enc2 = this._keyStr.indexOf(input.charAt(i++));
				enc3 = this._keyStr.indexOf(input.charAt(i++));
				enc4 = this._keyStr.indexOf(input.charAt(i++));

				chr1 = (enc1 << 2) | (enc2 >> 4);
				chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
				chr3 = ((enc3 & 3) << 6) | enc4;

				output = output + String.fromCharCode(chr1);

				if (enc3 !== 64) {
					output = output + String.fromCharCode(chr2);
				}
				if (enc4 !== 64) {
					output = output + String.fromCharCode(chr3);
				}
			}

			output = Base64._utf8_decode(output);

			return output;
		},

		// private method for UTF-8 encoding
		_utf8_encode: function (string) {
			string = string.replace(/\r\n/g, "\n");
			let utftext = "";

			for (let n = 0; n < string.length; n++) {
				let c = string.charCodeAt(n);

				if (c < 128) {
					utftext += String.fromCharCode(c);
				} else if (c > 127 && c < 2048) {
					utftext += String.fromCharCode((c >> 6) | 192);
					utftext += String.fromCharCode((c & 63) | 128);
				} else {
					utftext += String.fromCharCode((c >> 12) | 224);
					utftext += String.fromCharCode(((c >> 6) & 63) | 128);
					utftext += String.fromCharCode((c & 63) | 128);
				}
			}

			return utftext;
		},

		// private method for UTF-8 decoding
		_utf8_decode: function (utftext) {
			let string = "";
			let i = 0;
			let c = 0;
			let c2 = 0;
			let c3 = 0;

			while (i < utftext.length) {
				c = utftext.charCodeAt(i);

				if (c < 128) {
					string += String.fromCharCode(c);
					i++;
				} else if (c > 191 && c < 224) {
					c2 = utftext.charCodeAt(i + 1);
					string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
					i += 2;
				} else {
					c2 = utftext.charCodeAt(i + 1);
					c3 = utftext.charCodeAt(i + 2);
					string += String.fromCharCode(
						((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63),
					);
					i += 3;
				}
			}

			return string;
		},
	};
}

function attach_qTip(el, force = false) {
	if (el.hasClass("tooltipstered")) {
		if (force) {
			el.tooltipster("destroy");
		} else return;
	}
	let $url = el.attr("qtip_ajxhrf");
	let $text = el.attr("qtip_ttip");
	let $side = el.attr("qtip_side") || "right,left,bottom,top";
	let $theme =
		el.attr("qtip_theme") || "tooltipster-shadow tooltipster-shadow-yellow";
	let $b64text = el.attr("qtip_b64ttip");

	if (typeof $b64text !== "undefined") $text = Base64.decode($b64text);

	let $load = undefined;
	if (typeof $url !== "undefined") {
		if (typeof $text == "undefined") $text = "Загрузка...";
		$load = function (instance, helper) {
			let $origin = jQuery(helper.tooltip);

			if ($origin.data("loaded") !== true) {
				jQuery.get($url, function (data) {
					// порядок такой:
					//  1) предзагружаем картинки из ответа в кеш, чтобы при вставке HTML
					//     у <img> сразу были natural-размеры и тултип не "рос" уже после
					//     первого reposition
					//  2) instance.content(data) внутри tooltipster триггерит reposition;
					//     благодаря локальному патчу в tooltipster.bundle.js (realSize
					//     перед detach -> в SideTip.__reposition) координаты считаются по
					//     фактической высоте тултипа в DOM
					//  3) ExpandableCardInit добавляет в тултип absolute-toggle, который
					//     на flow не влияет, но финальный reposition в RAF*2 страхует от
					//     любых остаточных layout-shift'ов
					let setContent = function () {
						let st = instance.status();
						if (st.destroyed || !st.open) return;

						// прячем тултип на время финальной "усадки" — visibility:hidden,
						// в отличие от display:none, сохраняет layout (getBoundingClientRect
						// в патче tooltipster всё равно видит реальный размер контента).
						let tipEl = instance.elementTooltip();
						if (tipEl) tipEl.style.visibility = "hidden";

						instance.content(data);

						let $tip = jQuery(instance.elementTooltip());

						if (typeof ExpandableCardInit === "function") {
							$tip
								.find(".expandable-card-outer")
								.each(function (index, item) {
									ExpandableCardInit(item);
								});
						}

						// RAF×2 — даём браузеру довести layout, потом финальный reposition
						// и единым шагом возвращаем видимость уже на правильной позиции.
						requestAnimationFrame(function () {
							requestAnimationFrame(function () {
								let st2 = instance.status();
								if (st2.destroyed || !st2.open) return;
								instance.reposition();
								let finalTip = instance.elementTooltip();
								if (finalTip) finalTip.style.visibility = "";
							});
						});

						$origin.data("loaded", true);
					};

					let imgSrcs = [];
					try {
						let doc = new DOMParser().parseFromString(data, "text/html");
						doc.querySelectorAll("img[src]").forEach(function (img) {
							imgSrcs.push(img.getAttribute("src"));
						});
					} catch (e) {
						imgSrcs = [];
					}

					if (imgSrcs.length === 0) {
						setContent();
						return;
					}

					let pending = imgSrcs.length;
					let settled = false;
					let done = function () {
						if (settled) return;
						if (--pending <= 0) {
							settled = true;
							setContent();
						}
					};
					// safety: если что-то надолго зависло — всё равно показываем
					setTimeout(function () {
						if (settled) return;
						settled = true;
						setContent();
					}, 3000);
					imgSrcs.forEach(function (src) {
						let pre = new Image();
						pre.onload = pre.onerror = done;
						pre.src = src;
					});
				});
			}
		};
	}
	el.tooltipster({
		animationDuration: 100,
		content: $text,
		contentAsHTML: true,
		delay: 50,
		interactive: true,
		theme: $theme,
		// updateAnimation отключён: fade при подмене контента давал лишнее "моргание".
		updateAnimation: null,
		side: $side.split(","),
		functionBefore: $load,
		// trackTooltip отключён: после патча с realSize tooltipster ставит корректный
		// размер сразу — поллинговый трекер только зря дёргал reposition и моргал.
		trackTooltip: false,
	});
}

function attachAllTTips() {
	jQuery("[qtip_ajxhrf]")
		.not(".tooltipstered")
		.each(function () {
			attach_qTip(jQuery(this));
		});
	jQuery("[qtip_ttip]")
		.not(".tooltipstered")
		.each(function () {
			attach_qTip(jQuery(this));
		});
	jQuery("[qtip_b64ttip]")
		.not(".tooltipstered")
		.each(function () {
			attach_qTip(jQuery(this));
		});
}

jQuery(document).ready(function () {
	//setTimeout(attachAllTTips,500);
	//console.log("qtiip initialized");
	setInterval(attachAllTTips, 500);
});
