const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { createElement, useState, useEffect } = window.wp.element;

const initialSettings = {
    testMode: chargilySettings.testMode === 'yes' || chargilySettings.testMode === true || chargilySettings.testMode === '1',
    liveApiKeyPresent: chargilySettings.liveApiKeyPresent === 'yes' || chargilySettings.liveApiKeyPresent === true || chargilySettings.liveApiKeyPresent === '1',
    liveApiSecretPresent: chargilySettings.liveApiSecretPresent === 'yes' || chargilySettings.liveApiSecretPresent === true || chargilySettings.liveApiSecretPresent === '1',
    testApiKeyPresent: chargilySettings.testApiKeyPresent === 'yes' || chargilySettings.testApiKeyPresent === true || chargilySettings.testApiKeyPresent === '1',
    testApiSecretPresent: chargilySettings.testApiSecretPresent === 'yes' || chargilySettings.testApiSecretPresent === true || chargilySettings.testApiSecretPresent === '1',
    payment_methods: Array.isArray(chargilySettings.payment_methods) ? chargilySettings.payment_methods : ['EDAHABIA', 'CIB', 'QR'],
};

const shouldShowPaymentMethods = chargilySettings.show_payment_methods === 'yes';

if (!shouldShowPaymentMethods) {
    const style = document.createElement('style');
    style.innerHTML = `
      .Chargily-option {
         display: none !important;
      }
      .Chargily-option-no-show {
         display: block !important;
      }
      label.Chargily-label-no-show {
         display: flex !important;
         gap: 5px !important;
         justify-content: flex-start !important;
      }
   `;
    document.head.appendChild(style);
} else {
    const style = document.createElement('style');
    style.innerHTML = `
      .Chargily-option-no-show {
         display: none !important;
      }
   `;
    document.head.appendChild(style);
}

const assetsBaseUrl = chargilySettings.assetsUrl || `${window.location.origin}/wp-content/plugins/chargily-pay/assets/`;

const labels = {
    en: {
        chargilyPay: chargilySettings.title || "Chargily Pay™ (EDAHABIA/CIB)",
        description: chargilySettings.description || "Pay with your EDAHABIA/CIB card",
        edahabia: "EDAHABIA Card",
        cib: "CIB Card",
        app: "Chargily App",
        poweredBy: "provided by ",
        securePayment: "🔒 Secure E-Payment ",
        istestMode: "Test Mode is enabled.",
        TestWarningMessage: "You are in Test Mode but your Test API keys are missing.",
        TestLinkTextWarningMessage: "Enter your Test API keys.",
        LiveWarningMessage: "You are in Live Mode but your Live API keys are missing.",
    },
    ar: {
        chargilyPay: chargilySettings.title || "شارجيلي باي (الذهبية / CIB)",
        description: chargilySettings.description || "ادفع باستخدام بطاقتك الذهبية أو CIB",
        edahabia: "البطاقة الذهبية",
        cib: "البطاقة البنكية CIB",
        app: "تطبيق شارجيلي",
        poweredBy: "بواسطة ",
        securePayment: "🔒 بوابة دفع إلكتروني آمنة ",
        istestMode: "وضع التجربة مفعل.",
        TestWarningMessage: "أنت في وضع التجربة ولكن مفاتيح API الخاصة بوضع التجربة مفقودة.",
        TestLinkTextWarningMessage: "أدخل مفاتيح API الخاصة بوضع التجربة.",
        LiveWarningMessage: "أنت في وضع المباشر ولكن مفاتيح API الخاصة بالوضع المباشر مفقودة.",
    },
    fr: {
        chargilyPay: chargilySettings.title || "Chargily Pay™ (EDAHABIA/CIB)",
        description: chargilySettings.description || "Payez avec votre carte EDAHABIA/CIB",
        edahabia: "EDAHABIA Card",
        cib: "CIB Card",
        app: "Appli Chargily",
        poweredBy: "propulsé par",
        securePayment: "🔒 Passerelle de paiement sécurisée ",
        istestMode: "Le mode Test est activé.",
        TestWarningMessage: "Vous êtes en Mode Test mais vos clés API de test sont manquantes.",
        TestLinkTextWarningMessage: "Entrez vos clés API de test.",
        LiveWarningMessage: "Vous êtes en Mode Live mais vos clés API de production sont manquantes.",
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

const paymentMethodOptions = {
    EDAHABIA: {
        id: 'chargilyv2_edahabia',
        value: 'EDAHABIA',
        label: 'edahabia',
        image: 'edahabia-card.svg',
        alt: 'EDAHABIA Card',
        imageClass: 'edahabiaCardImage'
    },
    CIB: {
        id: 'chargilyv2_cib',
        value: 'CIB',
        label: 'cib',
        image: 'cib-card.svg',
        alt: 'CIB Card',
        imageClass: 'cibCardImage'
    },
    QR: {
        id: 'chargilyv2_app',
        value: 'chargily_app',
        label: 'app',
        image: 'qr-code.svg',
        alt: 'APP',
        imageClass: 'appCardImage'
    }
};

const PaymentMethodContent = () => {
    const [settings, setSettings] = useState(initialSettings);
    
    const enabledMethods = settings.payment_methods || ['EDAHABIA', 'CIB', 'QR'];
    
    const getDefaultMethod = () => {
        const cookieMethod = getCookie("chargily_payment_method");
        if (cookieMethod && enabledMethods.includes(
            cookieMethod === 'EDAHABIA' ? 'EDAHABIA' : 
            cookieMethod === 'chargily_app' ? 'QR' : 
            cookieMethod === 'CIB' ? 'CIB' : null
        )) {
            return cookieMethod;
        }
        
        if (enabledMethods.length > 0) {
            const firstMethod = enabledMethods[0];
            return firstMethod === 'EDAHABIA' ? 'EDAHABIA' : firstMethod;
        }
        
        return "EDAHABIA";
    };
    
    const [paymentMethod, setPaymentMethod] = useState(getDefaultMethod());

    const lang = (document.documentElement.lang || "en").split("-")[0];
    const label = labels[lang] || labels.en;

    const edahabiaCardImage = `${assetsBaseUrl}img/edahabia-card-v3.svg`;
    const cibCardImage = `${assetsBaseUrl}img/cib-card-v3.svg`;
    const appCardImage = `${assetsBaseUrl}img/chargily-logo-v3.svg`;
    const chargilyLogo = `${assetsBaseUrl}img/logo.svg`;

    useEffect(() => {
        setCookie("chargily_payment_method", paymentMethod, 7);
        setSettings(initialSettings);
    }, [paymentMethod]);

    const onPaymentMethodChange = (event) => {
        setPaymentMethod(event.target.value);
    };

    const renderPaymentOptions = () => {
        const options = [];
        let isFirst = true;
        
        enabledMethods.forEach(method => {
            if (paymentMethodOptions[method]) {
                const pm = paymentMethodOptions[method];
                const isChecked = paymentMethod === pm.value;
                
                let imageSrc = edahabiaCardImage;
                if (method === 'CIB') imageSrc = cibCardImage;
                if (method === 'QR') imageSrc = appCardImage;
                
                options.push(
                    createElement(
                        "div",
                        { 
                            key: pm.id,
                            className: "Chargily-option" 
                        },
                        createElement("input", {
                            type: "radio",
                            id: pm.id,
                            name: "chargily_payment_method",
                            value: pm.value,
                            onChange: onPaymentMethodChange,
                            checked: isChecked,
                        }),
                        createElement(
                            "label",
                            {
                                htmlFor: pm.id,
                                className: "Chargily",
                                "aria-label": label[pm.label],
                            },
                            createElement("span", {
                                style: { display: "flex", alignItems: "center" }
                            }),
                            createElement("div", {
                                className: "Chargily-card-text",
                            }, label[pm.label]),
                            createElement("img", {
                                className: pm.imageClass,
                                src: imageSrc,
                                alt: pm.alt,
                                style: { borderRadius: "4px" },
                            })
                        )
                    )
                );
                isFirst = false;
            }
        });
        
        return options;
    };

    const renderContent = () => {
        // التحقق من وجود مفاتيح API بناءً على الوضع
        if (settings.testMode) {
            if (!settings.testApiKeyPresent || !settings.testApiSecretPresent) {
                return createElement(
                    "div",
                    {},
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
                    {},
                    createElement("div", {}, label.istestMode),
                    ...renderPaymentOptions(),
                );
            }
        } else {
            if (!settings.liveApiKeyPresent || !settings.liveApiSecretPresent) {
                return createElement("p", {}, label.LiveWarningMessage);
            }
            return createElement(
                "div",
                {},
                ...renderPaymentOptions(),
            );
        }
    };

    return createElement(
        "div",
        { className: "Chargily-container" },
        renderContent()
    );
};

const lang = (document.documentElement.lang || 'en').split('-')[0];

const ChargilyPay = {
    name: "chargily_pay",
    label: labels[lang] ? labels[lang].chargilyPay : labels.en.chargilyPay,
    content: createElement(PaymentMethodContent),
    edit: createElement(PaymentMethodContent),
    canMakePayment: () => {
        return initialSettings.liveApiKeyPresent || initialSettings.testApiKeyPresent;
    },
    paymentMethodId: "chargily_pay",
    ariaLabel: labels[lang] ? labels[lang].chargilyPay : labels.en.chargilyPay,
    supports: {
        features: ["products"],
    },
};

registerPaymentMethod(ChargilyPay);
