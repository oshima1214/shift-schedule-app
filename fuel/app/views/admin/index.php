<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<title>シフト管理（管理者） - シフト表作成アプリ</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/knockout/3.5.1/knockout-min.js"></script>
<style>
	body { font-family: sans-serif; background: #f4f5f7; margin: 0; color: #222; }
	header { background: #1f2937; color: #fff; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; }
	header form { margin: 0; }
	header button { background: transparent; color: #ddd; border: 1px solid #555; border-radius: 4px; padding: 6px 12px; cursor: pointer; }
	main { max-width: 900px; margin: 24px auto; padding: 0 16px; }
	.panel { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
	h2 { font-size: 16px; margin: 0 0 16px; }
	table { width: 100%; border-collapse: collapse; }
	th, td { text-align: left; padding: 8px; border-bottom: 1px solid #eee; font-size: 14px; }
	.badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 12px; }
	.badge.pending { background: #fef3c7; color: #92400e; }
	.badge.confirmed { background: #d1fae5; color: #065f46; }
	button.primary { background: #2563eb; color: #fff; border: none; border-radius: 4px; padding: 4px 12px; cursor: pointer; font-size: 12px; }
	.error-box { color: #c0392b; font-size: 13px; margin-bottom: 12px; }
	.muted { color: #888; font-size: 12px; }
</style>
</head>
<body>
<header>
	<div>シフト表作成アプリ（管理者）／<?php echo $employee_name; ?> さん</div>
	<?php echo \Form::open(array('action' => 'auth/logout', 'method' => 'post')); ?>
		<button type="submit">ログアウト</button>
	<?php echo \Form::close(); ?>
</header>

<main>
	<div class="panel">
		<h2>全従業員のシフト希望一覧</h2>
		<div class="error-box" data-bind="foreach: errors"><div data-bind="text: $data"></div></div>
		<table>
			<thead>
				<tr><th>部署</th><th>従業員</th><th>勤務日</th><th>時間</th><th>備考</th><th>状態</th><th></th></tr>
			</thead>
			<tbody data-bind="foreach: shifts">
				<tr>
					<td data-bind="text: department_name"></td>
					<td data-bind="text: employee_name"></td>
					<td data-bind="text: work_date"></td>
					<td data-bind="text: start_time + ' - ' + end_time"></td>
					<td data-bind="text: note"></td>
					<td>
						<span class="badge pending" data-bind="visible: status() === 'pending', text: '希望中'"></span>
						<span class="badge confirmed" data-bind="visible: status() === 'confirmed', text: '確定'"></span>
					</td>
					<td>
						<!-- ko if: status() === 'pending' -->
						<button class="primary" data-bind="click: $parent.confirmShift">確定する</button>
						<!-- /ko -->
					</td>
				</tr>
			</tbody>
		</table>
		<p class="muted" data-bind="visible: shifts().length === 0">シフト希望はまだ登録されていません。</p>
	</div>
</main>

<script>
// CSRFトークンはCookieが常に最新（ローテーションされる）なので、
// ページ読み込み時の値をキャッシュせず、送信の都度Cookieから読み直す。
function getCsrfToken() {
	var m = document.cookie.match(/(?:^|;\s*)fuel_csrf_token=([^;]*)/);
	return m ? decodeURIComponent(m[1]) : '';
}

function apiRequest(url, payload) {
	return fetch(url, {
		method: 'POST',
		credentials: 'same-origin',
		headers: { 'Content-Type': 'application/json' },
		body: JSON.stringify(Object.assign({ fuel_csrf_token: getCsrfToken() }, payload || {}))
	}).then(function (res) {
		return res.json().then(function (body) {
			if (!res.ok) {
				var err = new Error('request failed');
				err.body = body;
				throw err;
			}
			return body;
		});
	});
}

function ShiftRow(data) {
	this.department_name = data.department_name;
	this.employee_name = data.employee_name;
	this.work_date = data.work_date;
	this.start_time = data.start_time;
	this.end_time = data.end_time;
	this.note = data.note || '';
	this.status = ko.observable(data.status);
	this.id = data.id;
}

function ViewModel() {
	var self = this;
	self.shifts = ko.observableArray([]);
	self.errors = ko.observableArray([]);

	self.loadShifts = function () {
		fetch('admin/list', { credentials: 'same-origin' })
			.then(function (res) { return res.json(); })
			.then(function (body) {
				self.shifts((body.data || []).map(function (row) { return new ShiftRow(row); }));
			});
	};

	self.confirmShift = function (row) {
		self.errors([]);
		apiRequest('admin/confirm/' + row.id).then(function () {
			row.status('confirmed');
		}).catch(function (err) {
			self.errors((err.body && err.body.errors) || ['確定に失敗しました。']);
		});
	};

	self.loadShifts();
}

ko.applyBindings(new ViewModel());
</script>
</body>
</html>
