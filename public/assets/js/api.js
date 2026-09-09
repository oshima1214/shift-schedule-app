/**
 * 画面共通のAjaxヘルパー。
 *
 * CSRFトークンはCookieにあるものが常に最新なので、ページ読み込み時の値を
 * キャッシュせず、送信の都度Cookieから読み直す。
 */
(function () {
  'use strict';

  function csrfToken() {
    var m = document.cookie.match(/(?:^|;\s*)fuel_csrf_token=([^;]*)/);
    return m ? decodeURIComponent(m[1]) : '';
  }

  function handle(res) {
    return res.json().then(function (body) {
      if (!res.ok) {
        var err = new Error('request failed');
        err.status = res.status;
        err.body = body;
        throw err;
      }
      return body;
    }).catch(function (e) {
      if (e.body) { throw e; }
      var err = new Error('invalid response');
      err.status = res.status;
      err.body = { errors: ['通信に失敗しました。時間をおいて試してください。'] };
      throw err;
    });
  }

  window.api = {
    /** GET（クエリはオブジェクトで渡す。空の値は送らない） */
    get: function (url, params) {
      var q = [];
      Object.keys(params || {}).forEach(function (k) {
        var v = params[k];
        if (v !== null && v !== undefined && v !== '') {
          q.push(encodeURIComponent(k) + '=' + encodeURIComponent(v));
        }
      });
      return fetch(url + (q.length ? '?' + q.join('&') : ''), {
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
