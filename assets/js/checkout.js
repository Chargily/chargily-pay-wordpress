const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { createElement, useState, useEffect } = window.wp.element;

const labels = {
   en: {
      chargilyPay: "Chargily Pay™ (EDAHABIA/CIB) ",
      description: "Pay with your EDAHABIA/CIB card",
      edahabia: "EDAHABIA",
      cib: "CIB Card",
      poweredBy: "provided by ",
      securePayment: "🔒 Secure E-Payment ",
      istestMode: "Test Mode is enabled.",
      TestWarningMessage: "You are in Test Mode but your Test API keys are missing.",
      TestLinkTextWarningMessage: "Enter your Test API keys.",
      LiveWarningMessage: "You are in Live Mode but your Live API keys are missing.",
   },
   ar: {
      chargilyPay: "شارجيلي باي (الذهبية / CIB) ",
      description: "ادفع باستخدام بطاقتك الذهبيالبنكية CIB",
      edahabia: "الذهبية",
      cib: "البطاقة البنكية Cib",
      poweredBy: "بواسطة ",
      securePayment: "🔒 بوابة دفع إلكتروني آمنة ",
      istestMode: "الTest Mode مفعل.",
      TestWarningMessage: "أنت في وضع التجربة ولكن مفاتيح الAPI لوضع التجربة الخاصة بك مفقودة.",
      TestLinkTextWarningMessage: "أدخل مفاتيح الAPI اللوضع التجربة الخاصة بك.",
      LiveWarningMessage: "أنت في وضع Live ولكن مفاتيح الAPI لوضع الLive الخاصه بك مفقودة.",
   },
   fr: {
      chargilyPay: "Chargily Pay™ (EDAHABIA/CIB) ",
      description: "Payez avec votre carte EDAHABIA/CIB",
      edahabia: "EDAHABIA",
      cib: "CIB Card",
      poweredBy: "🔒 Propulsé par",
      securePayment: "Passerelle de paiement électronique sécurisée.",
      istestMode: "Le mode Test est activé.",
      TestWarningMessage: "Vous êtes en Mode Test mais vos clés API de Mode Test sont manquantes.",
      TestLinkTextWarningMessage: "Entrez vos clés API de Mode Test.",
      LiveWarningMessage: "Vous êtes en Mode Live mais vos clés API de Mode Live sont manquantes.",
   },
};

function setCookie(name, value, days) {
   var expires = "";
   if (days) {
      var date = new Date();
      date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
      expires = "; expires=" + date.toUTCString();
   }
   document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

function getCookie(name) {
   var nameEQ = name + "=";
   var ca = document.cookie.split(";");
   for (var i = 0; i < ca.length; i++) {
      var c = ca[i];
      while (c.charAt(0) == " ") c = c.substring(1, c.length);
      if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
   }
   return null;
}

const PaymentMethodContent = () => {
   const [settings, setSettings] = useState({
      testMode: true,
      liveApiKeyPresent: false,
      liveApiSecretPresent: false,
      testApiKeyPresent: false,
      testApiSecretPresent: false,
   });

   const defaultMethod = getCookie("chargily_payment_method") || "EDAHABIA";
   const [paymentMethod, setPaymentMethod] = useState(defaultMethod);

   const lang = document.documentElement.lang;
   const label = labels[lang] || labels.en;

   const edahabiacardcib = `${window.location.origin}/wp-content/plugins/chargily-pay/assets/img/edahabia-card-cib.svg`;
   const edahabiaCardImage = `${window.location.origin}/wp-content/plugins/chargily-pay/assets/img/edahabia-card.svg`;
   const cibCardImage = `${window.location.origin}/wp-content/plugins/chargily-pay/assets/img/cib-card.svg`;
   const chargilyLogo = `${window.location.origin}/wp-content/plugins/chargily-pay/assets/img/logo.svg`;

   useEffect(() => {
      setCookie("chargily_payment_method", paymentMethod, 7);
      const randomVersion = Math.random().toString(36).substring(2, 15);
      const settingsUrl = `${window.location.origin}/wp-content/plugins/chargily-pay/templates/method-v2/chargily_data.json?v=${randomVersion}`;
      fetch(settingsUrl)
         .then((response) => response.json())
         .then((data) => setSettings(data));
   }, [paymentMethod]);

   const onPaymentMethodChange = (event) => {
      setPaymentMethod(event.target.value);
   };

   const renderContent = () => {
      if (settings.testMode) {
         if (!settings.testApiKeyPresent || !settings.testApiSecretPresent) {
            return createElement(
               "div",
               { className: "" },
               createElement("p", {}, label.TestWarningMessage),
               createElement(
                  "a",
                  {
                     href: "/wp-admin/admin.php?page=wc-settings&tab=checkout&section=chargily_pay",
                     target: "_blank",
                     style: { color: "black" },
                  },
                  createElement("p", {}, label.TestLinkTextWarningMessage)
               )
            );
         } else {
            return createElement(
               "div",
               { className: "" },
               createElement("div", {}, label.istestMode),
               createElement(
                  "div",
                  { className: "Chargily-option" },
                  createElement("input", {
                     type: "radio",
                     id: "chargilyv2_edahabia",
                     name: "chargily_payment_method",
                     value: "EDAHABIA",
                     onChange: onPaymentMethodChange,
                     checked: paymentMethod === "EDAHABIA",
                  }),
                  createElement(
                     "label",
                     {
                        htmlFor: "chargilyv2_edahabia",
                        className: "Chargily",
                        "aria-label": label.edahabia,
                     },
                     createElement("span",{
						 style: { display: "flex", alignItems: "center" } }
                     ),
                     createElement("div", {
                        className: "Chargily-card-text",
                        style: {},
                        bis_skin_checked: 1,
                     }, label.edahabia),
                     createElement("img", {
						 className: "edahabiaCardImage",
                        src: edahabiaCardImage,
                        alt: label.edahabia,
                        style: { borderRadius: "4px", },
                     })
                  )
               ),
               createElement(
                  "div",
                  { className: "Chargily-option" },
                  createElement("input", {
                     type: "radio",
                     id: "chargilyv2_cib",
                     name: "chargily_payment_method",
                     value: "CIB",
                     onChange: onPaymentMethodChange,
                     checked: paymentMethod === "CIB",
                  }),
                  createElement(
                     "label",
                     {
                        htmlFor: "chargilyv2_cib",
                        className: "Chargily",
                        "aria-label": label.cib,
                     },
                     createElement("span",{
						 style: { display: "flex", alignItems: "center" } }
                     ),
                     createElement("div", {
                        className: "Chargily-card-text",
                        style: {},
                        bis_skin_checked: 1,
                     }, label.cib),
                     createElement("img", {
						 className: "cibCardImage",
                        src: cibCardImage,
                        alt: label.cib,
                        style: {},
                     })
                  )
               ),
               createElement(
                  "p",
                  { style: {} },
                  label.securePayment,
                  label.poweredBy,
                  createElement(
                     "a",
                     {
                        class: "chlogo",
                        href: "https://chargily.com/business/pay",
                        target: "_blank",
                        style: { color: "black" },
                     },
                     createElement("img", {
                        src: chargilyLogo,
                        alt: "chargily",
                        style: { height: "30px"},
                     })
                  )
               )
            );
         }
      } else {
         if (!settings.liveApiKeyPresent || !settings.liveApiSecretPresent) {
            return createElement("p", {}, label.LiveWarningMessage);
         }
         return createElement(
            "div",
            { className: "" },
            createElement(
               "div",
               { className: "Chargily-option" },
               createElement("input", {
                  type: "radio",
                  id: "chargilyv2_edahabia",
                  name: "chargily_payment_method",
                  value: "EDAHABIA",
                  onChange: onPaymentMethodChange,
                  checked: paymentMethod === "EDAHABIA",
               }),
               createElement(
                     "label",
                     {
                        htmlFor: "chargilyv2_edahabia",
                        className: "Chargily",
                        "aria-label": label.edahabia,
                     },
                     createElement("span",{
						 style: { display: "flex", alignItems: "center" } }
                     ),
                     createElement("div", {
                        className: "Chargily-card-text",
                        style: {},
                        bis_skin_checked: 1,
                     }, label.edahabia),
                     createElement("img", {
						 className: "edahabiaCardImage",
                        src: edahabiaCardImage,
                        alt: label.edahabia,
                        style: { borderRadius: "4px"},
                     })
                  )
            ),
            createElement(
               "div",
               { className: "Chargily-option" },
               createElement("input", {
                  type: "radio",
                  id: "chargilyv2_cib",
                  name: "chargily_payment_method",
                  value: "CIB",
                  onChange: onPaymentMethodChange,
                  checked: paymentMethod === "CIB",
               }),
               createElement(
                     "label",
                     {
                        htmlFor: "chargilyv2_cib",
                        className: "Chargily",
                        "aria-label": label.cib,
                     },
                     createElement("span",{
						 style: { display: "flex", alignItems: "center" } }
                     ),
                     createElement("div", {
                        className: "Chargily-card-text",
                        style: {},
                        bis_skin_checked: 1,
                     }, label.cib),
                     createElement("img", {
						 className: "cibCardImage",
                        src: cibCardImage,
                        alt: label.cib,
                        style: {},
                     })
                  )
            ),
            createElement(
               "p",
               { style: {} },
               label.securePayment,
               label.poweredBy,
               createElement(
                  "a",
                  {
                     class: "chlogo",
                     href: "https://chargily.com/business/pay",
                     target: "_blank",
                     style: { color: "black" },
                  },
                  createElement("img", {
                     src: chargilyLogo,
                     alt: "chargily",
                     style: { height: "30px" },
                  })
               )
            )
         );
      }
   };
// marginBottom: "-7px"
   return createElement(
      "div",
      { className: "Chargily-container" },
      renderContent()
   );
};

const lang = document.documentElement.lang || "en";

const ChargilyPay = {
   name: "chargily_pay",
   label: labels[lang] ? labels[lang].chargilyPay : labels.en.chargilyPay,
   content: createElement(PaymentMethodContent),
   edit: createElement(PaymentMethodContent),
   canMakePayment: () => true,
   paymentMethodId: "chargily_pay",
   ariaLabel: labels[lang] ? labels[lang].chargilyPay : labels.en.chargilyPay,
   supports: {
      features: ["products"],
   },
};
registerPaymentMethod(ChargilyPay);

function tryToAddImage() {
   var labelElement = document.getElementById(
      "radio-control-wc-payment-method-options-chargily_pay__label"
   );
   if (labelElement && !imageAdded) {
      clearInterval(tryInterval);
      var imageElement = document.createElement("img");
      var edahabiacardcib = `${window.location.origin}/wp-content/plugins/chargily-pay/assets/img/edahabia-card-cib.svg`;
      imageElement.src = edahabiacardcib;
      labelElement.appendChild(imageElement);
      imageAdded = true;
   }
}

var imageAdded = false;
var tryInterval = setInterval(tryToAddImage, 200);
setTimeout(function () {
   clearInterval(tryInterval);
}, 20000);
