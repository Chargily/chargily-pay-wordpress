function updateCookieValue(element) {
	var selectedPaymentMethod = element.value;
	document.cookie = "chargily_payment_method=" + selectedPaymentMethod + "; path=/; max-age=" + (365 * 24 * 60 * 60);
}
