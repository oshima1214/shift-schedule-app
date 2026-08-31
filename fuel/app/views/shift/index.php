<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<title>シフト希望 - シフト表作成アプリ</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/knockout/3.5.1/knockout-min.js"></script>
<style>
	body { font-family: sans-serif; background: #f4f5f7; margin: 0; color: #222; }
	header { background: #1f2937; color: #fff; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; }
	header form { margin: 0; }
	header button { background: transparent; color: #ddd; border: 1px solid #555; border-radius: 4px; padding: 6px 12px; cursor: pointer; }
	main { max-width: 720px; margin: 24px auto; padding: 0 16px; }
	.panel { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
	h2 { font-size: 16px; margin: 0 0 16px; }
	.row { display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap; }
	.row label { font-size: 12px; color: #666; display: block; margin-bottom: 4px; }
	input[type=date], input[type=time], input[type=text] { padding: 6px; border: 1px solid #ccc; border-radius: 4px; }
	button.primary { background: #2563eb; color: #fff; border: none; border-radius: 4px; padding: 8px 16px; cursor: pointer; }
	button.danger { background: #dc2626; color: #fff; border: none; border-radius: 4px; padding: 4px 10px; cursor: pointer; font-size: 12px; }
	button.secondary { background: #eee; border: 1px solid #ccc; border-radius: 4px; padding: 4px 10px; cursor: pointer; font-size: 12px; }
	table { width: 100%; border-collapse: collapse; }
	th, td { text-align: left; padding: 8px; border-bottom: 1px solid #eee; font-size: 14px; }
	.badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 12px; }
	.badge.pending { background: #fef3c7; color: #92400e; }
	.badge.confirmed { background: #d1fae5; color: #065f46; }
	.error-box { color: #c0392b; font-size: 13px; margin-bottom: 12px; }
	.muted { color: #888; font-size: 12px; }
</style>
</head>
<body>
<header>
	<div>シフト表作成アプリ／<?php echo $employee_name; ?> さん</div>
	<?php echo \Form::open(array('action' => 'auth/logout', 'method' => 'post')); ?>
		<button type="submit">ログアウト</button>
	<?php echo \Form::close(); ?>
</header>

<main>
	<div class="panel">
		<h2>シフト希望を登録する（本日から<?php echo (int) $request_range_days; ?>日以内）</h2>
		<div class="error-box" data-bind="foreach: formErrors"><div data-bind="text: $data"></div></div>
		<div class="row">
			<div>
				<label>勤務日</label>
				<input type="date" data-bind="value: form.work_date">
			</div>
			<div>
				<label>開始</label>
				<input type="time" data-bind="value: form.start_time">
			</div>
			<div>
				<label>終了</label>
				<input type="time" data-bind="value: form.end_time">
			</div>
			<div style="flex:1">
				<label>備考</label>
				<input type="text" style="width:100%" maxlength="255" data-bind="value: form.note">
			</div>
		</div>
		<button class="primary" data-bind="click: submitForm, enable: !saving()">
			<span data-bind="text: form.id() ? '更新する' : '登録する'"></span>
		</button>
		<button class="secondary" data-bind="click: resetForm, visible: form.id()">キャンセル</button>
	</div>

	<div class="panel">
		<h2>自分のシフト希望一覧</h2>
		<table>
			<thead>
				<tr><th>勤務日</th><th>時間</th><th>備考</th><th>状態</th><th></th></tr>
			</thead>
			<tbody data-bind="foreach: shifts">
				<tr>
					<td data-bind="text: work_date"></td>
					<td data-bind="text: start_time + ' - ' + end_time"></td>
					<td data-bind="text: note"></td>
					<td>
						<span class="badge pending" data-bind="visible: status() === 'pending', text: '希望中'"></span>
						<span class="badge confirmed" data-bind="visible: status() === 'confirmed', text: '確定'"></span>
					</td>
					<td>
						<!-- ko if: status() === 'pending' -->
						<button class="secondary" data-bind="click: $parent.editShift">編集</button>
						<button class="danger" data-bind="click: $parent.deleteShift">削除</button>
						<!-- /ko -->
						<!-- ko if: status() === 'confirmed' -->
						<span class="muted">確定済のため編集不可</span>
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
	this.id = data.id;
	this.work_date = data.work_date;
	this.start_time = data.start_time;
	this.end_time = data.end_time;
	this.note = data.note || '';
	this.status = ko.observable(data.status);
}

function ViewModel() {
	var self = this;
	self.shifts = ko.observableArray([]);
	self.saving = ko.observable(false);
	self.formErrors = ko.observableArray([]);

	self.form = {
		id: ko.observable(null),
		work_date: ko.observable(''),
		start_time: ko.observable(''),
		end_time: ko.observable(''),
		note: ko.observable('')
	};

	self.resetForm = function () {
		self.form.id(null);
		self.form.work_date('');
		self.form.start_time('');
		self.form.end_time('');
		self.form.note('');
		self.formErrors([]);
	};

	self.loadShifts = function () {
		fetch('shift/list', { credentials: 'same-origin' })
			.then(function (res) { return res.json(); })
			.then(function (body) {
				self.shifts((body.data || []).map(function (row) { return new ShiftRow(row); }));
			});
	};

	self.editShift = function (row) {
		self.form.id(row.id);
		self.form.work_date(row.work_date);
		self.form.start_time(row.start_time);
		self.form.end_time(row.end_time);
		self.form.note(row.note);
		self.formErrors([]);
	};

	self.deleteShift = function (row) {
		if (!confirm('このシフト希望を削除しますか？')) {
			return;
		}
		apiRequest('shift/delete/' + row.id).then(function () {
			self.loadShifts();
		}).catch(function (err) {
			self.formErrors((err.body && err.body.errors) || ['削除に失敗しました。']);
		});
	};

	self.submitForm = function () {
		self.formErrors([]);
		self.saving(true);

		var payload = {
			work_date: self.form.work_date(),
			start_time: self.form.start_time(),
			end_time: self.form.end_time(),
			note: self.form.note()
		};

		var url = self.form.id() ? ('shift/update/' + self.form.id()) : 'shift/create';

		apiRequest(url, payload).then(function () {
			self.saving(false);
			self.resetForm();
			self.loadShifts();
		}).catch(function (err) {
			self.saving(false);
			self.formErrors((err.body && err.body.errors) || ['保存に失敗しました。']);
		});
	};

	self.loadShifts();
}

ko.applyBindings(new ViewModel());
</script>
</body>
</html>
