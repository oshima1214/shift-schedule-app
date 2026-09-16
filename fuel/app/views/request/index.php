<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>シフト希望一覧｜シフト表作成アプリ</title>
<link rel="stylesheet" href="<?php echo Uri::create('assets/css/app.css'); ?>">
<script src="<?php echo Uri::create('assets/js/knockout-3.5.1.js'); ?>"></script>
<script src="<?php echo Uri::create('assets/js/api.js'); ?>"></script>
</head>
<body>

<?php echo View::forge('partial/header', array(
  'employee'         => $employee,
  'employment_label' => '',
  'active'           => 'request',
)); ?>

<div class="wrap">
  <h1>シフト希望一覧</h1>
  <p class="lead">全従業員から提出されたシフト希望を、週と部署で絞り込んで確認できます。</p>

  <div class="screen">
    <div class="toolbar">
      <button class="btn" data-bind="click: prevWeek, disable: loading">前週</button>
      <span class="period" data-bind="text: weekLabel"></span>
      <button class="btn" data-bind="click: nextWeek, disable: loading">次週</button>

      <select class="sel push" data-bind="value: departmentId">
        <option value="">全部署</option>
        <?php foreach ($departments as $department): ?>
          <option value="<?php echo $department['id']; ?>"><?php echo $department['name']; ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="errors" data-bind="visible: errors().length">
      <ul data-bind="foreach: errors"><li data-bind="text: $data"></li></ul>
    </div>

    <div class="loading" data-bind="visible: loading">読み込み中…</div>

    <div class="tablescroll" data-bind="visible: !loading()">
      <table>
        <thead>
          <tr>
            <th style="width:13%">部署</th>
            <th style="width:16%">従業員</th>
            <th style="width:11%">日付</th>
            <th style="width:10%">開始</th>
            <th style="width:10%">終了</th>
            <th style="width:12%">状態</th>
            <th style="width:28%">却下理由</th>
          </tr>
        </thead>
        <tbody data-bind="foreach: rows">
          <tr>
            <td class="sub" data-bind="text: department_name"></td>
            <td data-bind="text: employee_name"></td>
            <td data-bind="text: date_label"></td>
            <td class="sub" data-bind="text: start_time"></td>
            <td class="sub" data-bind="text: end_time"></td>
            <td>
              <!-- ko if: status === 'requested' --><span class="tag warn">希望中</span><!-- /ko -->
              <!-- ko if: status === 'approved' --><span class="tag ok">確定</span><!-- /ko -->
              <!-- ko if: status === 'rejected' --><span class="tag ng">却下</span><!-- /ko -->
            </td>
            <td class="sub">
              <!-- ko if: reject_reason --><span data-bind="text: reject_reason"></span><!-- /ko -->
              <!-- ko ifnot: reject_reason --><span class="none">−</span><!-- /ko -->
            </td>
          </tr>
        </tbody>
      </table>
      <div class="empty-row" data-bind="visible: rows().length === 0">この週に提出されたシフト希望はありません。</div>
    </div>

    <div class="foot">
      <span><span class="swatch" style="background:var(--warn-bg)"></span>希望中</span>
      <span><span class="swatch" style="background:var(--ok-bg)"></span>確定</span>
      <span><span class="swatch" style="background:var(--ng-bg)"></span>却下</span>
      <span class="push">確定・却下の操作は「シフト表確定」画面から行います</span>
    </div>
  </div>
</div>

<script>
  function ViewModel() {
    var self = this;

    self.rows = ko.observableArray([]);
    self.week = ko.observable('');
    self.weekLabel = ko.observable('');
    self.prev = ko.observable('');
    self.next = ko.observable('');
    self.departmentId = ko.observable('');
    self.loading = ko.observable(true);
    self.errors = ko.observableArray([]);

    self.load = function (week) {
      self.loading(true);
      self.errors([]);
      api.get('<?php echo Uri::create('request/list'); ?>', {
        week: week,
        department_id: self.departmentId()
      }).then(function (body) {
        self.rows(body.rows);
        self.week(body.week);
        self.weekLabel(body.label);
        self.prev(body.prev_week);
        self.next(body.next_week);
        self.loading(false);
      }).catch(function (err) {
        self.loading(false);
        self.errors(api.messages(err, '一覧の取得に失敗しました。'));
      });
    };

    self.reload = function () { self.load(self.week()); };
    self.prevWeek = function () { self.load(self.prev()); };
    self.nextWeek = function () { self.load(self.next()); };

    // 部署の変更で再読込する。
    // event: { change: ... } を使うと、knockoutが値を書き戻す前にハンドラが
    // 走ることがあり、変更前の値でAPIを呼んでしまうため購読で行う。
    self.departmentId.subscribe(function () { self.reload(); });

    self.load('');
  }

  ko.applyBindings(new ViewModel());
</script>
</body>
</html>
