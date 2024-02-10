const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { createElement, useState, useEffect } = window.wp.element;

const labels = {
    en: {
        chargilyPay: 'Chargily Pay 1',
        description: 'Pay with your EDAHABIA/CIB card',
        edahabia: 'EDAHABIA',
        cib: 'CIB',
        Card: 'Card',
        poweredBy: 'Powered By',
        securePayment: 'Secure e-payment gateway.',
    },
    ar: {
        chargilyPay: 'شحنيلي باي',
        description: 'ادفع باستخدام بطاقتك الإداهبية/سيب',
        edahabia: 'الذهبية',
        cib: 'البطاقة',
        Card: 'البنكية',
        poweredBy: 'بتقنية',
        securePayment: 'بوابة الدفع الإلكتروني الآمنة.',
    },
    fr: {
        chargilyPay: 'Chargily Payer',
        description: 'Payez avec votre carte EDAHABIA/CIB',
        edahabia: 'EDAHABIA',
        cib: 'CIB',
        Card: 'Card',
        poweredBy: 'Propulsé par',
        securePayment: 'Passerelle de paiement électronique sécurisée.',
    }
};

function setCookie(name, value, days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

const PaymentMethodContent = () => {
    const defaultMethod = getCookie('chargily_payment_method') || 'EDAHABIA';
    const [paymentMethod, setPaymentMethod] = useState(defaultMethod);
    
    const lang = document.documentElement.lang;
    const label = labels[lang] || labels.en;

    const edahabiaCardImage = `${window.location.origin}/wp-content/plugins/chargily-pay/assets/img/edahabia-card.svg`;
    const cibCardImage = `${window.location.origin}/wp-content/plugins/chargily-pay/assets/img/cib-card.svg`;
    const chargilyLogo = `${window.location.origin}/wp-content/plugins/chargily-pay/assets/img/logo.svg`;

    useEffect(() => {
        setCookie('chargily_payment_method', paymentMethod, 7);
    }, [paymentMethod]);

    const onPaymentMethodChange = (event) => {
        setPaymentMethod(event.target.value);
    };

    return createElement('div', { className: 'Chargily-container' },
        createElement('div', { className: 'Chargily-option' },
            createElement('input', {
                type: 'radio',
                id: 'chargilyv2_edahabia',
                name: 'chargily_payment_method',
                value: 'EDAHABIA',
                onChange: onPaymentMethodChange,
                checked: paymentMethod === 'EDAHABIA'
            }),
            createElement('label', { htmlFor: 'chargilyv2_edahabia', className: 'Chargily', 'aria-label': label.edahabia },
                createElement('span', { style: { display: 'flex', alignItems: 'center' } },
                    createElement('div', { style: { opacity: 0 } }, 'card :'),
                    createElement('p', {}, label.edahabia)
                ),
                createElement('div', { className: 'Chargily-card-text', style: {}, 'bis_skin_checked': 1 }),
                createElement('img', { src: edahabiaCardImage, alt: label.edahabia, style: { borderRadius: '4px' } })
            )
        ),
        createElement('div', { className: 'Chargily-option' },
            createElement('input', {
                type: 'radio',
                id: 'chargilyv2_cib',
                name: 'chargily_payment_method',
                value: 'CIB',
                onChange: onPaymentMethodChange,
                checked: paymentMethod === 'CIB'
            }),
            createElement('label', { htmlFor: 'chargilyv2_cib', className: 'Chargily', 'aria-label': label.cib },
                createElement('span', { style: { display: 'flex', alignItems: 'center' } },
                    createElement('div', { style: { opacity: 0 } }, 'card :'),
                    createElement('p', { style: {} }, label.cib),
                    createElement('div', { style: { opacity: 0 } }, '-'),
                    createElement('p', {}, ' Card')
                ),
                createElement('div', { className: 'Chargily-card-text', style: {}, 'bis_skin_checked': 1 }),
                createElement('img', { src: cibCardImage, alt: label.cib, style: {} })
            )
        ),
        createElement('br', {}),
        createElement('a', { href: 'https://chargily.com/business/pay', target: '_blank', style: { color: 'black' } },
            label.poweredBy,
            createElement('img', { src: chargilyLogo, alt: 'chargily', style: {} })
        ),
        createElement('p', {}, createElement('img', { draggable: 'false', role: 'img', className: 'emoji', alt: '🔒', src: 'https://s.w.org/images/core/emoji/14.0.0/svg/1f512.svg' }), label.securePayment)
    );
};

const lang = document.documentElement.lang || 'en';

const ChargilyPay = {
    name: 'chargily_pay',
    label: labels[lang].chargilyPay,
    content: createElement(PaymentMethodContent),
    edit: createElement(PaymentMethodContent),
    canMakePayment: () => true,
    paymentMethodId: 'chargily_pay',
    ariaLabel: labels[lang].chargilyPay,
    supports: {
        features: ['products'],
    },
};
registerPaymentMethod(ChargilyPay);
