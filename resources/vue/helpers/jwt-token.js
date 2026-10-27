const SESSION_ERRORS = ["token_invalid", "token_expired", "token_not_provided", "unauthenticated"];

export default {
	setToken(token) {
		window.localStorage.setItem('jwt_token', token);
	},
	getToken() {
		return window.localStorage.getItem('jwt_token');
	},
	removeToken() {
		window.localStorage.removeItem('jwt_token');
	},
	// Wipe everything the app keeps in localStorage so a dead token can't wedge the next load.
	clearSession() {
		window.localStorage.clear();
	},
	// True when an API error means the stored token is no longer accepted (expired, bad signature,
	// user missing or inactive). jwt-auth 2 answers those with a 401 + "WWW-Authenticate: jwt-auth";
	// other 401s (wrong password, expired public report link) don't carry it.
	isInvalid(error) {
		const response = error && error.response;
		if (!response || response.status !== 401) {
			return false;
		}
		const data = response.data || {};
		return SESSION_ERRORS.includes(data.error) || /jwt-auth/i.test(response.headers['www-authenticate'] || '');
	}
}
