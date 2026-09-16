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
	<p class="lead">日付と従業員のマトリクスです。セルをクリックすると「希望中 → 確定 → 却下」の順に切り替わります。<br>
		希望中は週ごと・日ごとにまとめて確定でき、確定したあとでも取り消して希望中に戻せます。</p>

	<div class="screen">
		<div class="toolbar">
			<button class="btn" data-bind="click: prevWeek, disable: loading">前週</button>
			<span class="period" data-bind="text: weekLabel"></span>
			<button class="btn" data-bind="click: nextWeek, disable: loading">次週</button>

			<select class="sel" data-bind="value: departmentId">
				<option value="">全部署</option>
				<?php foreach ($departments as $department): ?>
					<option value="<?php echo $department['id']; ?>"><?php echo $department['name']; ?></option>
				<?php endforeach; ?>
			</select>

			<!-- 確定したあとでもやり直せるよう、取り消しを確定の隣に置く -->
			<button class="btn push"
				data-bind="click: undoWeek,
					disable: saving() || !weekApprovedIds().length,
					text: '確定を取り消す（' + weekApprovedIds().length + '件）'"></button>

			<button class="btn btn-primary"
				data-bind="click: approveWeek,
					disable: saving() || !weekRequestedIds().length,
					text: '希望中をまとめて確定（' + weekRequestedIds().length + '件）'"></button>
		</div>

		<!-- 出勤者が0人の日は見落としやすいので、表の上でも知らせる。
		     そもそも希望が1件もない週は、表の「提出されたシフト希望はありません」で足りるので出さない。 -->
		<div class="warnbar" data-bind="visible: !loading() && rows().length && zeroDayLabels().length">
			<strong>確定した出勤者がいない日があります：</strong>
			<span data-bind="text: zeroDayLabels().join('　')"></span>
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
									attr: { title: $parents[1].cellTitle($data) }"></button>
							<!-- /ko -->
							<!-- ko ifnot: $data -->
							<span class="empty">−</span>
							<!-- /ko -->
						</td>
						<!-- /ko -->
					</tr>
				</tbody>

				<!-- 日別の人員サマリ。確定人員が0の日をひと目で分かるようにする -->
				<tfoot data-bind="visible: rows().length">
					<tr class="sumrow">
						<th>確定人員</th>
						<!-- ko foreach: summary -->
						<td>
							<span class="count" data-bind="text: approved + '人', css: { zero: is_zero }"></span>
							<!-- 確定した日をその日だけ希望中に戻せるようにする -->
							<!-- ko if: approved -->
							<button class="minibtn"
								data-bind="click: function (data, event) { $parent.undoDay($index()); },
									disable: $parent.saving,
									attr: { title: label + 'の確定を取り消して希望中に戻します' }">取消</button>
							<!-- /ko -->
						</td>
						<!-- /ko -->
					</tr>
					<tr class="sumrow">
						<th>希望中</th>
						<!-- ko foreach: summary -->
						<td>
							<!-- ko if: requested -->
							<button class="minibtn"
								data-bind="text: requested + '件を確定',
									click: function (data, event) { $parent.approveDay($index()); },
									disable: $parent.saving,
									attr: { title: label + 'の希望中をまとめて確定します' }"></button>
							<!-- /ko -->
							<!-- ko ifnot: requested -->
							<span class="empty">−</span>
							<!-- /ko -->
						</td>
						<!-- /ko -->
					</tr>
				</tfoot>
			</table>
			<div class="empty-row" data-bind="visible: rows().length === 0">この週に提出されたシフト希望はありません。</div>
		</div>

		<div class="foot">
			<span><span class="swatch" style="background:var(--warn-bg)"></span>希望中</span>
			<span><span class="swatch" style="background:var(--ok-bg)"></span>確定</span>
			<span><span class="swatch" style="background:var(--ng-bg)"></span>却下</span>
			<span class="push">セルをクリックで状態切替／確定は「取消」でいつでも希望中に戻せます</span>
		</div>
	</div>
</div>

<!-- 却下理由の入力 -->
<div class="modal-backdrop" data-bind="visible: showReject" style="display:none">
	<div class="modal">
		<div class="modal-head">却下理由の入力</div>

		<div class="modal-body">
			<div class="errors" data-bind="visible: rejectErrors().length" style="border:none;border-radius:6px;margin-bottom:14px">
				<ul data-bind="foreach: rejectErrors"><li data-bind="text: $data"></li></ul>
			</div>

			<p class="hint" style="margin:0 0 10px" data-bind="text: rejectTargetLabel"></p>

			<label class="field-label" for="reject_reason">却下理由（任意）</label>
			<textarea class="inp" id="reject_reason" rows="3" style="width:100%;resize:vertical"
				maxlength="<?php echo (int) $reason_max_length; ?>"
				data-bind="value: rejectReason, valueUpdate: 'input'"></textarea>
			<div class="hint">
				入力すると、従業員のシフト希望入力画面にも表示されます。空のままでも却下できます。<?php echo (int) $reason_max_length; ?>文字以内。
			</div>
		</div>

		<div class="modal-foot">
			<button class="btn" data-bind="click: closeReject">キャンセル</button>
			<button class="btn btn-danger" data-bind="click: submitReject, disable: saving">却下する</button>
		</div>
	</div>
</div>

<script>
// 希望中 → 確定 → 却下 → 希望中 の順に回す
var STATUS_ORDER = ['requested', 'approved', 'rejected'];
var STATUS_LABEL = { requested: '希望中', approved: '確定', rejected: '却下' };

function ViewModel() {
	var self = this;

	self.rows = ko.observableArray([]);
	self.days = ko.observableArray([]);
	self.summary = ko.observableArray([]);
	self.week = ko.observable('');
	self.weekLabel = ko.observable('');
	self.prev = ko.observable('');
	self.next = ko.observable('');
	self.departmentId = ko.observable('');
	self.loading = ko.observable(true);
	self.saving = ko.observable(false);
	self.errors = ko.observableArray([]);

	// 却下理由の入力用
	self.showReject = ko.observable(false);
	self.rejectReason = ko.observable('');
	self.rejectErrors = ko.observableArray([]);
	self.rejectTargetLabel = ko.observable('');
	self.rejectIds = [];

	/** 確定した出勤者が0人の日 */
	self.zeroDayLabels = ko.computed(function () {
		return self.summary().filter(function (day) {
			return day.is_zero;
		}).map(function (day) {
			return day.label;
		});
	});

	/** 表示中の週から、指定した状態のセルのIDを集める */
	self.weekIdsOf = function (status) {
		var ids = [];
		self.rows().forEach(function (row) {
			row.cells.forEach(function (cell) {
				if (cell && cell.status === status) { ids.push(cell.id); }
			});
		});
		return ids;
	};

	/** 指定した曜日（列）から、指定した状態のセルのIDを集める */
	self.dayIdsOf = function (index, status) {
		var ids = [];
		self.rows().forEach(function (row) {
			var cell = row.cells[index];
			if (cell && cell.status === status) { ids.push(cell.id); }
		});
		return ids;
	};

	self.weekRequestedIds = ko.computed(function () { return self.weekIdsOf('requested'); });
	self.weekApprovedIds = ko.computed(function () { return self.weekIdsOf('approved'); });

	/** セルのツールチップ。却下済みは理由も見せる */
	self.cellTitle = function (cell) {
		if (cell.status === 'rejected' && cell.reject_reason) {
			return '却下理由：' + cell.reject_reason + '（クリックで状態を切り替え）';
		}
		return 'クリックで状態を切り替え';
	};

	self.load = function (week) {
		self.loading(true);
		self.errors([]);
		api.get('<?php echo Uri::create('schedule/list'); ?>', {
			week: week,
			department_id: self.departmentId()
		}).then(function (body) {
			self.days(body.days);
			self.rows(body.rows);
			self.summary(body.summary);
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

	/**
	 * 状態変更を送る。1件なら単体API、複数なら一括APIを使う。
	 *
	 * @param {number[]} ids
	 * @param {string}   status
	 * @param {string}   reason  却下理由（却下以外ではサーバ側で無視される）
	 */
	self.applyStatus = function (ids, status, reason) {
		var single = ids.length === 1;
		var url = single
			? '<?php echo Uri::create('schedule/status'); ?>/' + ids[0]
			: '<?php echo Uri::create('schedule/bulk_status'); ?>';
		var payload = { status: status, reject_reason: reason || '' };

		if (!single) { payload.ids = ids; }

		self.saving(true);

		return api.post(url, payload).then(function (body) {
			self.saving(false);
			// 人員サマリも作り直す必要があるため、週ごと読み直す
			self.load(self.week());
			return body;
		}).catch(function (err) {
			self.saving(false);
			throw err;
		});
	};

	/** セルクリックで状態を切り替える（画面遷移なしで即時反映） */
	self.toggle = function (cell) {
		self.errors([]);

		var index = STATUS_ORDER.indexOf(cell.status);
		var next = STATUS_ORDER[(index + 1) % STATUS_ORDER.length];

		// 却下するときは理由を必ず記録する
		if (next === 'rejected') {
			self.openReject([cell.id], 'このシフト希望を却下します。');
			return;
		}

		self.applyStatus([cell.id], next).catch(function (err) {
			self.errors(api.messages(err, '状態の変更に失敗しました。'));
		});
	};

	/** 週全体の希望中をまとめて確定する */
	self.approveWeek = function () {
		var ids = self.weekRequestedIds();
		if (!ids.length) { return; }
		if (!confirm('この週の希望中 ' + ids.length + ' 件をまとめて確定します。よろしいですか？')) { return; }
		self.approve(ids);
	};

	/** 指定した日の希望中をまとめて確定する */
	self.approveDay = function (index) {
		var ids = self.dayIdsOf(index, 'requested');
		var day = self.summary()[index];
		if (!ids.length) { return; }
		if (!confirm(day.label + ' の希望中 ' + ids.length + ' 件をまとめて確定します。よろしいですか？')) { return; }
		self.approve(ids);
	};

	self.approve = function (ids) {
		self.errors([]);
		self.applyStatus(ids, 'approved').catch(function (err) {
			self.errors(api.messages(err, '一括確定に失敗しました。'));
		});
	};

	/** 週全体の確定を取り消して希望中に戻す（確定したあとのやり直し） */
	self.undoWeek = function () {
		var ids = self.weekApprovedIds();
		if (!ids.length) { return; }
		if (!confirm('この週の確定 ' + ids.length + ' 件を取り消して、希望中に戻します。よろしいですか？')) { return; }
		self.undo(ids);
	};

	/** 指定した日の確定を取り消して希望中に戻す */
	self.undoDay = function (index) {
		var ids = self.dayIdsOf(index, 'approved');
		var day = self.summary()[index];
		if (!ids.length) { return; }
		if (!confirm(day.label + ' の確定 ' + ids.length + ' 件を取り消して、希望中に戻します。よろしいですか？')) { return; }
		self.undo(ids);
	};

	self.undo = function (ids) {
		self.errors([]);
		self.applyStatus(ids, 'requested').catch(function (err) {
			self.errors(api.messages(err, '確定の取り消しに失敗しました。'));
		});
	};

	self.openReject = function (ids, label) {
		self.rejectIds = ids;
		self.rejectReason('');
		self.rejectErrors([]);
		self.rejectTargetLabel(label);
		self.showReject(true);
	};

	self.closeReject = function () {
		self.showReject(false);
		self.rejectErrors([]);
	};

	self.submitReject = function () {
		// 却下理由は任意。空のままでも却下できる。
		var reason = self.rejectReason().trim();

		self.rejectErrors([]);
		self.applyStatus(self.rejectIds, 'rejected', reason).then(function () {
			self.showReject(false);
		}).catch(function (err) {
			self.rejectErrors(api.messages(err, '却下に失敗しました。'));
		});
	};

	self.load('');
}

ko.applyBindings(new ViewModel());
</script>
</body>
</html>
