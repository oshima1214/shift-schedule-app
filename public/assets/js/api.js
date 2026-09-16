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

  function handle(res) {
    return res.json().then(function (body) {
      if (!res.ok) {
        const request_error = new Error('request failed');
        request_error.status = res.status;
        request_error.body = body;
        throw request_error;
      }
      return body;
    }).catch(function (e) {
      if (e.body) { throw e; }
      const request_error = new Error('invalid response');
      request_error.status = res.status;
      request_error.body = { errors: ['通信に失敗しました。時間をおいて試してください。'] };
      throw request_error;
    });
  }

  window.api = {
    /** GET（クエリはオブジェクトで渡す。空の値は送らない） */
    get: function (url, params) {
      const query_parts = [];
      Object.keys(params || {}).forEach(function (k) {
        const param_value = params[k];
        if (param_value !== null && param_value !== undefined && param_value !== '') {
          query_parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(param_value));
        }
      });
      return fetch(url + (query_parts.length ? '?' + query_parts.join('&') : ''), {
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
      }).then(handle);
    },

    /** POST（JSONボディ。CSRFトークンを必ず添える） */
    post: function (url, payload) {
      return fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(Object.assign({ fuel_csrf_token: csrfToken() }, payload || {}))
      }).then(handle);
    },

    /** エラーオブジェクトからメッセージ配列を取り出す */
    messages: function (err, fallback) {
      if (err && err.body && err.body.errors && err.body.errors.length) {
        return err.body.errors;
      }
      return [fallback || '処理に失敗しました。'];
    }
  };
})();
