<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Update - Wish2Padel</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/image/w2p.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('assets/image/w2p.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset('assets/image/w2p.png') ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= asset('assets/css/stylee.css?v=12') ?>" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
</head>
<body>

<?php view('partials.club_navbar'); ?>

<section class="container mt-5 mb-5 py-5 bg-white rounded shadow">
<h2>Edit Club</h2>

<?php if ($club): ?>
<form method="post" enctype="multipart/form-data" action="<?= asset('club/update') ?>">
    <!-- Basic Info -->
    <input type="hidden" name="id" value="<?= $club['id'] ?>">
    <div class="row g-3">
        <div class="col-md-6">
            <label>Club Name</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($club['name']) ?>" required>
        </div>
        <div class="col-md-6">
            <label>Logo</label>
            <input type="file" name="logo" class="form-control">
            <?php if ($club['logo_url']): ?>
                <img src="<?= asset('uploads/club/' . $club['logo_url']) ?>" alt="logo" style="height:50px;" class="mt-2">
            <?php endif; ?>
        </div>
        <div class="col-md-4">
            <label>Street</label>
            <input type="text" name="street" class="form-control" value="<?= htmlspecialchars($club['street']) ?>">
        </div>
        <div class="col-md-4">
            <label>Postal Code</label>
            <input type="text" name="postal_code" class="form-control" value="<?= htmlspecialchars($club['postal_code']) ?>">
        </div>
        <div class="col-md-4">
            <label>City</label>
            <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($club['city']) ?>">
        </div>
        <div class="col-md-4">
            <label>Zone</label>
            <select name="zone" class="form-select">
                <?php $zones = ["North Zone","South Zone","East Zone","West Zone","Central Zone"];
                foreach($zones as $z){ $sel=($club['zone']==$z)?"selected":""; echo "<option value='$z' $sel>$z</option>"; } ?>
            </select>
        </div>
        <div class="col-md-4">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($club['phone']) ?>">
        </div>
        <div class="col-md-4">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($club['email']) ?>">
        </div>
        <div class="col-md-6">
            <label>Website</label>
            <input type="text" name="website" class="form-control" value="<?= htmlspecialchars($club['website']) ?>">
        </div>
        <div class="col-12">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($club['description']) ?></textarea>
        </div>
    </div>

    <!-- Pistas -->
    <div class="mt-4">
        <h5>Fields</h5>
        <div id="pistas-wrapper">
            <?php foreach($pistas as $p): ?>
            <div class="row g-2 mb-2 pista-item">
                <input type="hidden" name="pista_id[]" value="<?= $p['id'] ?>">
                <div class="col-md-6"><input type="text" name="pista_name[]" class="form-control" value="<?= htmlspecialchars($p['name']) ?>"></div>
                <div class="col-md-4"><input type="number" name="pista_quantity[]" class="form-control" value="<?= $p['quantity'] ?>"></div>
                <div class="col-md-2"><button type="button" class="btn btn-danger remove-pista" data-id="<?= $p['id'] ?>" data-type="pista">Remove</button></div>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="add-pista" class="btn btn-sm btn-primary">Add Field</button>
    </div>

    <!-- Schedules -->
    <div class="mt-4" style="display:none">
        <h5>Schedules</h5>
        <div id="schedules-wrapper">
            <?php foreach($schedules as $s): ?>
            <div class="row g-2 mb-2 schedule-item">
                <input type="hidden" name="schedule_id[]" value="<?= $s['id'] ?>">
                <div class="col-md-3">
                    <select name="schedule_day[]" class="form-select">
                        <?php $days=["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"];
                        foreach($days as $d){ $sel=($s['day']==$d)?"selected":""; echo "<option value='$d' $sel>$d</option>"; } ?>
                    </select>
                </div>
                <div class="col-md-3"><input type="time" name="open_time[]" class="form-control" value="<?= $s['open_time'] ?>"></div>
                <div class="col-md-3"><input type="time" name="close_time[]" class="form-control" value="<?= $s['close_time'] ?>"></div>
                <div class="col-md-3">
                    <button type="button" 
                            class="btn btn-danger remove-schedule" 
                            data-id="<?= $s['id'] ?>" 
                            data-type="schedule">
                        Remove
                    </button>
                </div>

            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="add-schedule" class="btn btn-sm btn-primary">Add Schedule</button>
    </div>

    <!-- Photos -->
    <div class="mt-4">
        <h5>Photos</h5>
        <div class="row g-2 mb-2">
            <?php foreach($photos as $ph): ?>
            <div class="col-md-3">
                <img src="<?= asset('uploads/club/' . $ph['url']) ?>" class="img-fluid rounded mb-1">
                <button type="button" class="btn btn-danger btn-sm remove-photo w-100" data-id="<?= $ph['id'] ?>" data-type="photo">Remove</button>
            </div>
            <?php endforeach; ?>
        </div>
        <div id="photos-wrapper">
            <div class="mb-2 photo-item">
                <input type="file" name="photos[]" class="form-control">
                <!-- No ID for new photos -->
            </div>
        </div>
        <button type="button" id="add-photo" class="btn btn-sm btn-primary">Add Photo</button>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-success">Update Club</button>
        <a href="<?= asset('club/dashboard') ?>" class="btn btn-secondary">Ok</a>
        <p class="text-black">
            If you have deleted something, please click <strong>Ok</strong> to confirm. 
            Do <strong>not</strong> click <strong>Update</strong> or the deletion will be canceled.
        </p>
    </div>

</form>
<?php else: ?>
<div class="alert alert-warning">Data club tidak ditemukan.</div>
<?php endif; ?>
</section>

<?php view('partials.footer'); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    document.body.addEventListener("click", function(e) {
        if (e.target.classList.contains("remove-pista") ||
            e.target.classList.contains("remove-schedule") ||
            e.target.classList.contains("remove-photo")) {

            // If it's a new item (no data-id), just remove DOM
            if (!e.target.dataset.id) {
                e.target.closest(".pista-item, .schedule-item, .photo-item").remove();
                return;
            }

            if (!confirm("Are you sure you want to delete this item?")) return;

            const btn = e.target;
            const id = btn.dataset.id;
            const type = btn.dataset.type;

            fetch("<?= asset('club/update') ?>", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `action=delete&delete_type=${encodeURIComponent(type)}&delete_id=${encodeURIComponent(id)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const wrapper = btn.closest(".pista-item, .schedule-item, .col-md-3");
                    if (wrapper) wrapper.remove();
                } else {
                    alert("Delete failed!");
                }
            });
        }
    });
});
</script>


<script>
document.addEventListener('DOMContentLoaded', () => {

    // ===== PISTAS (Fields) =====
    const pistasWrapper = document.getElementById('pistas-wrapper');
    document.getElementById('add-pista').addEventListener('click', () => {
        const div = document.createElement('div');
        div.classList.add('row','g-2','mb-2','pista-item');
        div.innerHTML = `
            <input type="hidden" name="pista_id[]" value="">
            <div class="col-md-6"><input type="text" name="pista_name[]" class="form-control" placeholder="Field Name"></div>
            <div class="col-md-4"><input type="number" name="pista_quantity[]" class="form-control" placeholder="Quantity"></div>
            <div class="col-md-2"><button type="button" class="btn btn-danger remove-pista">Remove</button></div>
        `;
        pistasWrapper.appendChild(div);
    });

    // ===== SCHEDULES =====
    const schedulesWrapper = document.getElementById('schedules-wrapper');
    document.getElementById('add-schedule').addEventListener('click', () => {
        const div = document.createElement('div');
        div.classList.add('row','g-2','mb-2','schedule-item');
        const daysOptions = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"]
            .map(d => `<option value="${d}">${d}</option>`).join('');
        div.innerHTML = `
            <input type="hidden" name="schedule_id[]" value="">
            <div class="col-md-3">
                <select name="schedule_day[]" class="form-select">${daysOptions}</select>
            </div>
            <div class="col-md-3"><input type="time" name="open_time[]" class="form-control"></div>
            <div class="col-md-3"><input type="time" name="close_time[]" class="form-control"></div>
            <div class="col-md-3"><button type="button" class="btn btn-danger remove-schedule">Remove</button></div>
        `;
        schedulesWrapper.appendChild(div);
    });

    // ===== PHOTOS =====
    const photosWrapper = document.getElementById('photos-wrapper');
    document.getElementById('add-photo').addEventListener('click', () => {
        const div = document.createElement('div');
        div.classList.add('mb-2','photo-item');
        div.innerHTML = `
            <input type="file" name="photos[]" class="form-control">
            <button type="button" class="btn btn-danger btn-sm remove-photo mt-1">Remove</button>
        `;
        photosWrapper.appendChild(div);
    });

});
</script>

</body>
</html>
