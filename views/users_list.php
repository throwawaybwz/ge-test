<?php $inc('header'); ?>

<section>
	<table class="primary">
		<thead>
			<tr>
				<th>Name</th>
				<th>E-Mail</th>
				<th>Active?</th>
				<th>Facebook ID</th>
				<th>Created</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($users_list as $u): ?>
			<tr>
				<td><?=$escape($u->name)?></td>
				<td><?=$escape($u->email)?></td>
				<td><?=($u->active ? 'Yes' : 'No')?></td>
				<td><?=$escape($u->facebook_id ?: 'No')?></td>
				<td><?=$escape($u->created)?></td>
				<td>
					<?php if ($user->admin): ?>
					<a class="button" href="/user/edit?id=<?=(int)$u->id?>">Edit</a>
					<a class="button warning" href="/user/delete?id=<?=(int)$u->id?>">Delete</a>
					<?php endif; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</section>

<?php $inc('footer'); ?>