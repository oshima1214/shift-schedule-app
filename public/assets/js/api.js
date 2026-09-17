/**
 * 画面共通のAjaxヘルパー。
 *
 * CSRFトークンはCookieにあるものが常に最新なので、ページ読み込み時の値を
 * キャッシュせず、送信の都度Cookieから読み直す。
 */
(function () {
  'use strict';

  function csrfToken() {
    const matched_cookie = document.cookie.match(/(?:^|;\s*)fuel_csrf_token=([^;]*)/);
    return matched_cookie ? decodeURIComponent(matched_cookie[1]) : '';
  }

  function handle(response) {
    return response.json().then(function (response_body) {
      if (!response.ok) {
        const request_error = new Error('request failed');
        request_error.status = response.status;
        request_error.body = response_body;
        throw request_error;
      }
      return response_body;
    }).catch(function (caught_error) {
      if (caught_error.body) { throw caught_error; }
      const request_error = new Error('invalid response');
      request_error.status = response.status;
      request_error.body = { errors: ['通信に失敗しました。時間をおいて試してください。'] };
      throw request_error;
    });
  }

  window.api = {
    /** GET（クエリはオブジェクトで渡す。空の値は送らない） */
    get: function (endpoint_url, query_params) {
      const query_parts = [];
      Object.keys(query_params || {}).forEach(function (param_name) {
        const param_value = query_params[param_name];
        if (param_value !== null && param_value !== undefined && param_value !== '') {
          query_parts.push(encodeURIComponent(param_name) + '=' + encodeURIComponent(param_value));
        }
      });
      return fetch(endpoint_url + (query_parts.length ? '?' + query_parts.join('&') : ''), {
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
      }).then(handle);
    },

    /** POST（JSONボディ。CSRFトークンを必ず添える） */
    post: function (endpoint_url, request_payload) {
      return fetch(endpoint_url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(Object.assign({ fuel_csrf_token: csrfToken() }, request_payload || {}))
      }).then(handle);
    },

    /** エラーオブジェクトからメッセージ配列を取り出す */
    messages: function (request_error, fallback_message) {
      if (request_error && request_error.body && request_error.body.errors && request_error.body.errors.length) {
        return request_error.body.errors;
      }
      return [fallback_message || '処理に失敗しました。'];
    }
  };
})();
