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

			// SideTip-плагин на каждом открытии пересоздаёт DOM-элемент тултипа
			// (__close сбрасывает _$tooltip = null, __create делает новый), поэтому
			// в момент functionBefore instance.elementTooltip() ещё null. Чтобы
			// успеть навесить qtip-settling ДО того, как tooltipster вставит
			// тултип в body и пользователь увидит промежуточный размер, ловим
			// событие 'created' — оно триггерится сразу после того, как новый
			// _$tooltip создан, но ещё до contentInsert/reposition/appendTo(body).
			// !important в css-правиле нужен потому, что сам tooltipster пишет
			// inline visibility в своих обработчиках (см. __scrollHandler).
			if (!instance.__qtipHookBound) {
				instance.__qtipHookBound = true;
				instance._on("created", function () {
					let tip = instance.elementTooltip();
					if (tip) tip.classList.add("qtip-settling");
				});
			}

			// finalize: запускается и при первом, и при повторном открытии.
			// К моменту вызова контент уже вставлен в тултип (tooltipster's
			// __contentInsert + appendTo(body) уже отработали), и можно
			// инициализировать карточки, дождаться декодирования картинок
			// и сделать единственный финальный reposition.
			let finalize = function () {
				let $tip = jQuery(instance.elementTooltip());

				if (typeof ExpandableCardInit === "function") {
					$tip.find(".expandable-card-outer").each(function (index, item) {
						ExpandableCardInit(item);
					});
				}

				let waitImgs = $tip
					.find("img")
					.toArray()
					.map(function (img) {
						if (img.complete && img.naturalWidth > 0) return null;
						if (typeof img.decode === "function") {
							return img.decode().catch(function () {});
						}
						return new Promise(function (resolve) {
							let off = function () {
								img.removeEventListener("load", off);
								img.removeEventListener("error", off);
								resolve();
							};
							img.addEventListener("load", off);
							img.addEventListener("error", off);
						});
					})
					.filter(Boolean);

				let finish = function () {
					let st = instance.status();
					if (st.destroyed || !st.open) return;
					// RAF×2 — даём браузеру довести layout (шрифты/последний reflow),
					// затем reposition по финальной геометрии и убираем "усадку".
					requestAnimationFrame(function () {
						requestAnimationFrame(function () {
							let st2 = instance.status();
							if (st2.destroyed || !st2.open) return;
							instance.reposition();
							let finalTip = instance.elementTooltip();
							if (finalTip) finalTip.classList.remove("qtip-settling");
						});
					});
				};

				if (waitImgs.length === 0) finish();
				else Promise.all(waitImgs).then(finish, finish);
			};

			if ($origin.data("loaded") !== true) {
				// Первое открытие — тянем контент по ajax.
				jQuery.get($url, function (data) {
					let st = instance.status();
					if (st.destroyed || !st.open) return;

					// content(data, true) — наш патч в tooltipster.bundle.js
					// подавляет автоматический reposition, который иначе сработал
					// бы на ещё не "усевшейся" высоте контента (картинки не дошли
					// до natural size) и зафиксировал бы маленький размер со скроллом.
					instance.content(data, true);
					$origin.data("loaded", true);
					finalize();
				});
			} else {
				// Повторное открытие — контент уже cached в tooltipster.
				// Ждём, пока tooltipster закончит свой open-flow
				// (__contentInsert + appendTo body), и догоняем своим финалом.
				requestAnimationFrame(finalize);
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
