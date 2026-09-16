<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>従業員管理｜シフト表作成アプリ</title>
<link rel="stylesheet" href="<?php echo Uri::create('assets/css/app.css'); ?>">
<script src="<?php echo Uri::create('assets/js/knockout-3.5.1.js'); ?>"></script>
<script src="<?php echo Uri::create('assets/js/api.js'); ?>"></script>
</head>
<body>

<?php echo View::forge('partial/header', array(
  'employee'         => $employee,
  'employment_label' => '',
  'active'           => 'employee',
  'menu'             => $menu,
)); ?>

<div class="wrap">
  <h1>従業員管理</h1>
  <p class="lead">従業員の登録・編集・削除を行います。削除は論理削除のため、過去のシフト履歴は残ります。</p>

  <div class="screen">
    <div class="toolbar">
      <select class="sel" data-bind="value: departmentId">
        <option value="">全部署</option>
        <?php foreach ($departments as $department): ?>
          <option value="<?php echo $department['id']; ?>"><?php echo $department['name']; ?></option>
        <?php endforeach; ?>
      </select>

      <select class="sel" data-bind="value: employmentType">
        <option value="">全雇用形態</option>
        <?php foreach ($employment_types as $key => $label): ?>
          <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
        <?php endforeach; ?>
      </select>

      <button class="btn btn-primary push" data-bind="click: openCreate">＋ 新規登録</button>
    </div>

    <div class="errors" data-bind="visible: errors().length">
      <ul data-bind="foreach: errors"><li data-bind="text: $data"></li></ul>
    </div>

    <div class="loading" data-bind="visible: loading">読み込み中…</div>

    <div class="table-scroll" data-bind="visible: !loading()">
      <table class="employee-table">
        <colgroup>
          <col class="col-name">
          <col class="col-email">
          <col class="col-department">
          <col class="col-employment">
          <col class="col-role">
          <col class="col-action">
        </colgroup>
        <thead>
          <tr>
            <th>氏名</th>
            <th>メールアドレス</th>
            <th>部署</th>
            <th>雇用形態</th>
            <th>権限</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody data-bind="foreach: rows">
          <tr>
            <td data-bind="text: name"></td>
            <td class="sub ellip" data-bind="text: email, attr: { title: email }"></td>
            <td class="sub" data-bind="text: department_name"></td>
            <td>
              <span class="tag" data-bind="css: $parent.typeTag(employment_type), text: employment_type_label"></span>
            </td>
            <td class="sub" data-bind="text: role_label"></td>
            <td>
              <div class="actions">
                <button class="btn" data-bind="click: $parent.openEdit">編集</button>
                <button class="btn btn-danger" data-bind="click: $parent.remove, disable: is_self, attr: { title: is_self ? '自分自身は削除できません' : '' }">削除</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <div class="empty-row" data-bind="visible: rows().length === 0">該当する従業員がいません。</div>
    </div>

    <div class="pager" data-bind="visible: !loading()">
      <button class="btn" data-bind="click: prevPage, disable: page() <= 1">前へ</button>
      <span><span class="cur" data-bind="text: page"></span> / <span data-bind="text: totalPages"></span></span>
      <button class="btn" data-bind="click: nextPage, disable: page() >= totalPages()">次へ</button>
      <span class="pager-total">全 <span data-bind="text: total"></span> 件</span>
    </div>
  </div>
</div>

<!-- 登録・編集フォーム -->
<div class="modal-backdrop" data-bind="css: { 'is-open': showForm }">
  <div class="modal">
    <div class="modal-head" data-bind="text: editingId() ? '従業員情報の編集' : '従業員の新規登録'"></div>

    <div class="modal-body">
      <div class="errors errors-boxed" data-bind="visible: formErrors().length">
        <ul data-bind="foreach: formErrors"><li data-bind="text: $data"></li></ul>
      </div>

      <div class="form-grid">
        <div>
          <label class="field-label">氏名 <span class="req">*</span></label>
          <input class="inp" type="text" maxlength="100" data-bind="value: form.name">
        </div>
        <div>
          <label class="field-label">メールアドレス <span class="req">*</span></label>
          <input class="inp" type="email" maxlength="255" data-bind="value: form.email">
        </div>
        <div>
          <label class="field-label">所属部署 <span class="req">*</span></label>
          <select class="sel" data-bind="value: form.department_id">
            <?php foreach ($departments as $department): ?>
              <option value="<?php echo $department['id']; ?>"><?php echo $department['name']; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="field-label">雇用形態 <span class="req">*</span></label>
          <select class="sel" data-bind="value: form.employment_type">
            <?php foreach ($employment_types as $key => $label): ?>
              <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="field-label">権限 <span class="req">*</span></label>
          <select class="sel" data-bind="value: form.role">
            <?php foreach ($roles as $key => $label): ?>
              <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="field-label">パスワード <span class="req" data-bind="visible: !editingId()">*</span></label>
          <input class="inp" type="password" autocomplete="new-password" data-bind="value: form.password">
          <div class="hint" data-bind="visible: editingId">変更する場合のみ入力（<?php echo (int) $password_min_length; ?>文字以上）。再設定するとログインのロックも解除されます。</div>
          <div class="hint" data-bind="visible: !editingId()"><?php echo (int) $password_min_length; ?>文字以上</div>
        </div>
      </div>
    </div>

    <div class="modal-foot">
      <button class="btn" data-bind="click: closeForm">キャンセル</button>
      <button class="btn btn-primary" data-bind="click: submit, disable: saving, text: editingId() ? '更新' : '登録'"></button>
    </div>
  </div>
</div>

<script>
  // 雇用形態ごとのバッジの色
  // 新規登録時に最初から選んでおく部署。DOMの並び順に依存しないようサーバから受け取る。
  const DEFAULT_DEPARTMENT_ID = <?php echo json_encode($default_department_id); ?>;

  const TYPE_TAG = { full_time: 'sage', part_time: 'blue', part: 'pur' };

  function ViewModel() {
    const self = this;

    self.typeTag = function (type) { return TYPE_TAG[type] || 'blue'; };

    self.rows = ko.observableArray([]);
    self.page = ko.observable(1);
    self.totalPages = ko.observable(1);
    self.total = ko.observable(0);
    self.departmentId = ko.observable('');
    self.employmentType = ko.observable('');
    self.loading = ko.observable(true);
    self.errors = ko.observableArray([]);

    self.showForm = ko.observable(false);
    self.editingId = ko.observable(null);
    self.formErrors = ko.observableArray([]);
    self.saving = ko.observable(false);

    self.form = {
      name: ko.observable(''),
      email: ko.observable(''),
      department_id: ko.observable(''),
      employment_type: ko.observable('part_time'),
      role: ko.observable('employee'),
      password: ko.observable('')
    };

    self.load = function (page) {
      self.loading(true);
      self.errors([]);
      api.get('<?php echo Uri::create('employee/list'); ?>', {
        page: page,
        department_id: self.departmentId(),
        employment_type: self.employmentType()
      }).then(function (body) {
        self.rows(body.rows);
        self.page(body.page);
        self.totalPages(body.total_pages);
        self.total(body.total);
        self.loading(false);
      }).catch(function (err) {
        self.loading(false);
        self.errors(api.messages(err, '一覧の取得に失敗しました。'));
      });
    };

    self.reloadFirstPage = function () { self.load(1); };
    self.prevPage = function () { if (self.page() > 1) { self.load(self.page() - 1); } };
    self.nextPage = function () { if (self.page() < self.totalPages()) { self.load(self.page() + 1); } };

    // 絞り込みの変更で1ページ目から読み直す。
    // event: { change: ... } を使うと、knockoutが値を書き戻す前にハンドラが
    // 走ることがあり、変更前の値でAPIを呼んでしまうため購読で行う。
    self.departmentId.subscribe(function () { self.reloadFirstPage(); });
    self.employmentType.subscribe(function () { self.reloadFirstPage(); });

    self.openCreate = function () {
      self.editingId(null);
      self.formErrors([]);
      self.form.name('');
      self.form.email('');
      self.form.department_id(DEFAULT_DEPARTMENT_ID);
      self.form.employment_type('part_time');
      self.form.role('employee');
      self.form.password('');
      self.showForm(true);
    };

    self.openEdit = function (row) {
      self.editingId(row.id);
      self.formErrors([]);
      self.form.name(row.name);
      self.form.email(row.email);
      self.form.department_id(String(row.department_id));
      self.form.employment_type(row.employment_type);
      self.form.role(row.role);
      self.form.password('');
      self.showForm(true);
    };

    self.closeForm = function () {
      self.showForm(false);
      self.formErrors([]);
    };

    self.submit = function () {
      self.formErrors([]);
      self.saving(true);

      const request_payload = {
        name: self.form.name(),
        email: self.form.email(),
        department_id: self.form.department_id(),
        employment_type: self.form.employment_type(),
        role: self.form.role(),
        password: self.form.password()
      };
      const endpoint_url = self.editingId()
        ? '<?php echo Uri::create('employee/update'); ?>/' + self.editingId()
        : '<?php echo Uri::create('employee/create'); ?>';

      api.post(endpoint_url, request_payload).then(function () {
        self.saving(false);
        self.showForm(false);
        self.load(self.page());
      }).catch(function (err) {
        self.saving(false);
        self.formErrors(api.messages(err, '保存に失敗しました。'));
      });
    };

    self.remove = function (row) {
      if (!confirm(row.name + ' さんを削除しますか？（過去のシフト履歴は残ります）')) { return; }
      self.errors([]);
      api.post('<?php echo Uri::create('employee/delete'); ?>/' + row.id).then(function () {
        self.load(self.page());
      }).catch(function (err) {
        self.errors(api.messages(err, '削除に失敗しました。'));
      });
    };

    self.load(1);
  }

  ko.applyBindings(new ViewModel());
</script>
</body>
</html>
