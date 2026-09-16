<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>シフト希望入力｜シフト表作成アプリ</title>
<link rel="stylesheet" href="<?php echo Uri::create('assets/css/app.css'); ?>">
<script src="<?php echo Uri::create('assets/js/knockout-3.5.1.js'); ?>"></script>
<script src="<?php echo Uri::create('assets/js/api.js'); ?>"></script>
</head>
<body>

<?php echo View::forge('partial/header', array(
  'employee'         => $employee,
  'employment_label' => $employment_label,
  'active'           => 'shift',
)); ?>

<div class="wrap">
  <h1>シフト希望入力</h1>
  <p class="lead">週ごとに、自分のシフト希望を登録・編集・削除できます。確定・却下された希望は変更できません。</p>

  <div class="screen">
    <div class="toolbar center">
      <button class="btn" data-bind="click: prevWeek, disable: loading">前週</button>
      <span class="period" data-bind="text: weekLabel"></span>
      <button class="btn" data-bind="click: nextWeek, disable: loading">次週</button>
    </div>

    <div class="errors" data-bind="visible: errors().length">
      <ul data-bind="foreach: errors"><li data-bind="text: $data"></li></ul>
    </div>

    <div class="loading" data-bind="visible: loading">読み込み中…</div>

    <div class="tablescroll" data-bind="visible: !loading()">
      <table class="shift-table">
        <colgroup>
          <col class="col-date">
          <col class="col-start">
          <col class="col-end">
          <col class="col-state">
          <col class="col-action">
        </colgroup>
        <thead>
          <tr>
            <th>日付</th>
            <th>開始</th>
            <th>終了</th>
            <th>状態</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody data-bind="foreach: rows">
          <tr data-bind="css: { 'is-empty': !status, 'is-locked': status === 'approved' || status === 'rejected' }">
            <td>
              <span class="date" data-bind="text: date_label"></span><span class="dow"
                data-bind="text: '（' + dow + '）', css: { 'dow-sat': dow === '土', 'dow-sun': dow === '日' }"></span>
            </td>
            <td class="time" data-bind="text: start_time || '−'"></td>
            <td class="time" data-bind="text: end_time || '−'"></td>
            <td>
              <!-- ko if: status === 'requested' --><span class="state state-requested">希望中</span><!-- /ko -->
              <!-- ko if: status === 'approved' --><span class="state state-approved">確定</span><!-- /ko -->
              <!-- ko if: status === 'rejected' -->
              <span class="state state-rejected">却下</span>
              <!-- 管理者が入力した却下理由をそのまま見せる -->
              <!-- ko if: reject_reason -->
              <div class="reason" data-bind="text: '理由：' + reject_reason"></div>
              <!-- /ko -->
              <!-- ko ifnot: reject_reason -->
              <div class="reason reason-none">理由の記載はありません</div>
              <!-- /ko -->
              <!-- /ko -->
              <!-- ko ifnot: status --><span class="state state-none">未提出</span><!-- /ko -->
            </td>
            <td>
              <div class="rowactions">
                <!-- ko if: status === 'requested' -->
                <button class="iconbtn" title="編集する" aria-label="このシフト希望を編集する"
                  data-bind="click: $parent.startEdit">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                  </svg>
                </button>
                <button class="iconbtn is-danger" title="削除する" aria-label="このシフト希望を削除する"
                  data-bind="click: $parent.removeRow">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/>
                    <path d="M10 11v6"/><path d="M14 11v6"/>
                  </svg>
                </button>
                <!-- /ko -->

                <!-- ko if: status === 'approved' || status === 'rejected' -->
                <span class="lockmark"
                  data-bind="attr: { title: status === 'approved' ? '確定済みのため変更できません' : '却下済みのため変更できません' }">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" role="img"
                    data-bind="attr: { 'aria-label': status === 'approved' ? '確定済みのため変更できません' : '却下済みのため変更できません' }">
                    <rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                  </svg>
                </span>
                <!-- /ko -->

                <!-- ko ifnot: status -->
                <button class="addbtn" aria-label="この日のシフト希望を登録する"
                  data-bind="click: $parent.startCreate">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" aria-hidden="true">
                    <path d="M12 5v14"/><path d="M5 12h14"/>
                  </svg>登録
                </button>
                <!-- /ko -->
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="form">
      <div class="form-label" data-bind="text: editingId() ? 'シフト希望の編集' : '新規登録'"></div>
      <div class="form-row">
        <!-- 登録済みの日は選べないよう、選択できる日だけを出す -->
        <select class="sel" data-bind="options: selectableDays, optionsText: 'label', optionsValue: 'date',
          value: form.work_date, enable: selectableDays().length"></select>
        <select class="sel" data-bind="options: timeOptions, value: form.start_time, enable: selectableDays().length"></select>
        <span class="range-sep">〜</span>
        <select class="sel" data-bind="options: timeOptions, value: form.end_time, enable: selectableDays().length"></select>
        <button class="btn btn-primary"
          data-bind="click: submit, disable: saving() || !selectableDays().length, text: editingId() ? '更新' : '登録'"></button>
        <button class="btn" data-bind="click: cancelEdit, visible: editingId">キャンセル</button>
      </div>
      <div class="hint" data-bind="visible: !loading() && !selectableDays().length">
        この週は7日すべてに希望を登録済みです。変更する場合は一覧の「編集」から行ってください。
      </div>
    </div>
  </div>
</div>

<script>
  const TIME_OPTIONS = <?php echo json_encode($time_options, JSON_UNESCAPED_UNICODE); ?>;

  function ViewModel() {
    const self = this;

    self.rows = ko.observableArray([]);
    self.week = ko.observable('');
    self.weekLabel = ko.observable('');
    self.prev = ko.observable('');
    self.next = ko.observable('');
    self.loading = ko.observable(true);
    self.saving = ko.observable(false);
    self.errors = ko.observableArray([]);
    self.editingId = ko.observable(null);
    self.timeOptions = TIME_OPTIONS;

    self.form = {
      work_date: ko.observable(''),
      start_time: ko.observable('09:00'),
      end_time: ko.observable('13:00')
    };

    /**
     * 日付セレクトに出す日。
     * 新規登録では「まだ希望を出していない日」だけ、
     * 編集中はそれに加えて「編集対象の日」も選べるようにする。
     */
    self.selectableDays = ko.computed(function () {
      const editing_request_id = self.editingId();

      return self.rows().filter(function (row) {
        return ! row.id || row.id === editing_request_id;
      });
    });

    /** 選択できる最初の日をフォームに入れる */
    self.resetFormDate = function () {
      const selectable_days = self.selectableDays();
      self.form.work_date(selectable_days.length ? selectable_days[0].date : '');
    };

    /** 指定週を読み込む（画面遷移なし） */
    self.load = function (week) {
      self.loading(true);
      self.errors([]);
      api.get('<?php echo Uri::create('shift/list'); ?>', { week: week }).then(function (body) {
        self.rows(body.rows);
        self.week(body.week);
        self.weekLabel(body.label);
        self.prev(body.prev_week);
        self.next(body.next_week);
        self.loading(false);
        self.cancelEdit();
      }).catch(function (err) {
        self.loading(false);
        self.errors(api.messages(err, '一覧の取得に失敗しました。'));
      });
    };

    self.prevWeek = function () { self.load(self.prev()); };
    self.nextWeek = function () { self.load(self.next()); };

    self.startCreate = function (row) {
      self.editingId(null);
      self.errors([]);
      self.form.work_date(row.date);
      self.form.start_time('09:00');
      self.form.end_time('13:00');
    };

    self.startEdit = function (row) {
      self.editingId(row.id);
      self.errors([]);
      self.form.work_date(row.date);
      self.form.start_time(row.start_time);
      self.form.end_time(row.end_time);
    };

    self.cancelEdit = function () {
      self.editingId(null);
      self.errors([]);
      self.resetFormDate();
    };

    self.removeRow = function (row) {
      if (!confirm('この日のシフト希望を削除しますか？')) { return; }
      self.errors([]);
      api.post('<?php echo Uri::create('shift/delete'); ?>/' + row.id).then(function () {
        self.load(self.week());
      }).catch(function (err) {
        self.errors(api.messages(err, '削除に失敗しました。'));
      });
    };

    self.submit = function () {
      self.errors([]);
      self.saving(true);

      const request_payload = {
        work_date: self.form.work_date(),
        start_time: self.form.start_time(),
        end_time: self.form.end_time()
      };
      const endpoint_url = self.editingId()
        ? '<?php echo Uri::create('shift/update'); ?>/' + self.editingId()
        : '<?php echo Uri::create('shift/create'); ?>';

      api.post(endpoint_url, request_payload).then(function () {
        self.saving(false);
        self.load(self.week());
      }).catch(function (err) {
        self.saving(false);
        self.errors(api.messages(err, '保存に失敗しました。'));
      });
    };

    self.load('');
  }

  ko.applyBindings(new ViewModel());
</script>
</body>
</html>
