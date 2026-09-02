<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>シフト表確定｜シフト表作成アプリ</title>
<link rel="stylesheet" href="<?php echo Uri::create('assets/css/app.css'); ?>">
<script src="<?php echo Uri::create('assets/js/knockout-3.5.1.js'); ?>"></script>
<script src="<?php echo Uri::create('assets/js/api.js'); ?>"></script>
</head>
<body>

<?php echo View::forge('partial/header', array(
	'employee'         => $employee,
	'employment_label' => '',
	'active'           => 'schedule',
)); ?>

<div class="wrap">
	<h1>シフト表確定</h1>
	<p class="lead">日付と従業員のマトリクスです。セルをクリックすると「希望中 → 確定 → 却下」の順に切り替わります。</p>

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
			<table class="matrix">
				<thead>
					<tr>
						<th style="width:24%">従業員</th>
						<!-- ko foreach: days -->
						<th data-bind="text: dow"></th>
						<!-- /ko -->
					</tr>
				</thead>
				<tbody data-bind="foreach: rows">
					<tr>
						<td>
							<span data-bind="text: employee_name"></span>
							<span class="sub" style="color:var(--faint);font-size:11px" data-bind="text: ' / ' + department_name"></span>
						</td>
						<!-- ko foreach: cells -->
						<td>
							<!-- ko if: $data -->
							<button class="cell"
								data-bind="css: {
										warn: $data.status === 'requested',
										ok: $data.status === 'approved',
										ng: $data.status === 'rejected'
									},
									text: $data.status === 'rejected' ? '却下' : $data.time,
									click: function () { $parents[1].toggle($data); },
									attr: { title: 'クリックで状態を切り替え' }"></button>
							<!-- /ko -->
							<!-- ko ifnot: $data -->
							<span class="empty">−</span>
							<!-- /ko -->
						</td>
						<!-- /ko -->
					</tr>
				</tbody>
			</table>
			<div class="empty-row" data-bind="visible: rows().length === 0">この週に提出されたシフト希望はありません。</div>
		</div>

		<div class="foot">
			<span><span class="swatch" style="background:var(--warn-bg)"></span>希望中</span>
			<span><span class="swatch" style="background:var(--ok-bg)"></span>確定</span>
			<span><span class="swatch" style="background:var(--ng-bg)"></span>却下</span>
			<span class="push">セルをクリックで状態切替</span>
		</div>
	</div>
</div>

<script>
// 希望中 → 確定 → 却下 → 希望中 の順に回す
var STATUS_ORDER = ['requested', 'approved', 'rejected'];

function ViewModel() {
	var self = this;

	self.rows = ko.observableArray([]);
	self.days = ko.observableArray([]);
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
		api.get('<?php echo Uri::create('schedule/list'); ?>', {
			week: week,
			department_id: self.departmentId()
		}).then(function (body) {
			self.days(body.days);
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

	/** セルクリックで状態を切り替える（画面遷移なしで即時反映） */
	self.toggle = function (cell) {
		self.errors([]);

		var index = STATUS_ORDER.indexOf(cell.status);
		var next = STATUS_ORDER[(index + 1) % STATUS_ORDER.length];

		api.post('<?php echo Uri::create('schedule/status'); ?>/' + cell.id, { status: next }).then(function () {
			// 返ってきた結果でセルだけ差し替える
			self.load(self.week());
		}).catch(function (err) {
			self.errors(api.messages(err, '状態の変更に失敗しました。'));
		});
	};

	self.load('');
}

ko.applyBindings(new ViewModel());
</script>
</body>
</html>
