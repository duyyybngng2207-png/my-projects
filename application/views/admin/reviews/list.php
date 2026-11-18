<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php /** @var array $reviews_list */ /** @var string $pagination_links */ /** @var int $offset */ ?>

<h2>Đánh giá của khách</h2>
<hr>

<div class="table-responsive">
<table class="table table-bordered text-center align-middle">
    <thead>
        <tr>
            <th>STT</th>
            <th>Khách sạn</th>
            <th>Người gửi</th>
            <th>Email</th>
            <th>Sao</th>
            <th>Nội dung</th>
            <th>Ngày gửi</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($reviews_list)): ?>
            <tr><td colspan="9" class="text-muted py-4">Chưa có đánh giá nào.</td></tr>
        <?php endif; ?>

        <?php foreach ($reviews_list as $key => $item): ?>
            <tr>
                <th><?php echo $offset + $key + 1; ?></th>
                <td><?php echo $item->hotel_name !== null ? html_escape($item->hotel_name) : '<span class="text-muted">(đã xoá)</span>'; ?></td>
                <td><?php echo html_escape($item->name); ?></td>
                <td><?php echo html_escape($item->email); ?></td>
                <td class="text-warning text-nowrap"><?php echo str_repeat('★', (int) $item->rating); ?></td>
                <td class="text-start"><?php echo html_escape(mb_strimwidth((string) $item->content, 0, 120, '...', 'UTF-8')); ?></td>
                <td><?php echo date('d/m/Y', strtotime($item->created_at)); ?></td>
                <td>
                    <?php if ($item->is_approved): ?>
                        <span class="badge bg-success">Đã duyệt</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Chờ duyệt</span>
                    <?php endif; ?>
                </td>
                <td class="text-nowrap">
                    <?php echo form_open('admin/reviews/setApproval/' . $item->id, ['class' => 'd-inline']); ?>
                        <?php echo form_hidden('is_approved', $item->is_approved ? 0 : 1); ?>
                        <button type="submit" class="btn btn-sm <?php echo $item->is_approved ? 'btn-warning' : 'btn-success'; ?>">
                            <?php echo $item->is_approved ? 'Bỏ duyệt' : 'Duyệt'; ?>
                        </button>
                    <?php echo form_close(); ?>
                    <?= form_open('admin/reviews/deleteReview/' . $item->id, [
                        'class'    => 'd-inline',
                        'onsubmit' => "return confirm('Xóa đánh giá này?');"
                    ]) ?>
                    <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                    <?= form_close() ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<div class="mt-3">
    <?php echo $pagination_links; ?>
</div>