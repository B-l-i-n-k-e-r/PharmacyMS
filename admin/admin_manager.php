<?php
session_start();
include_once('../connect_db.php');

if(isset($_SESSION['username'])){
    $id=$_SESSION['admin_id'];
    $username=$_SESSION['username'];
}else{
    header("location:index.php");
    exit();
}

$message = '';
$message1 = '';
$form = ['first_name'=>'','last_name'=>'','staff_id'=>'','postal_address'=>'','phone'=>'','email'=>'','username'=>''];

if(isset($_POST['submit'])){
    $fname=$_POST['first_name'];
    $lname=$_POST['last_name'];
    $sid=$_POST['staff_id'];
    $postal=$_POST['postal_address'];
    $phone=$_POST['phone'];
    $email=$_POST['email'];
    $user=$_POST['username'];
    $pas=$_POST['password'];

    $form = [
        'first_name'=>$fname, 'last_name'=>$lname, 'staff_id'=>$sid,
        'postal_address'=>$postal, 'phone'=>$phone, 'email'=>$email, 'username'=>$user
    ];

    $stmt1 = $con->prepare("SELECT COUNT(*) FROM manager WHERE username = :user");
    $stmt1->execute([':user' => $user]);
    
    if($stmt1->fetchColumn() > 0){
        $message="That username is already taken. Choose a different one.";
    }else{
        $stmt = $con->prepare("INSERT INTO manager(first_name,last_name,staff_id,postal_address,phone,email,username,password,date)
                               VALUES(:fname, :lname, :sid, :postal, :phone, :email, :user, :pas, NOW())");
        
        $sql = $stmt->execute([
            ':fname'  => $fname,
            ':lname'  => $lname,
            ':sid'    => $sid,
            ':postal' => $postal,
            ':phone'  => $phone,
            ':email'  => $email,
            ':user'   => $user,
            ':pas'    => $pas
        ]);

        if($sql) {
            header("location:admin_manager.php?added=1");
            exit();
        }else{
            $message1="Could not save the manager. Try again.";
        }
    }
}

$stmtView = $con->query("SELECT * FROM manager");
$rows = $stmtView->fetchAll(PDO::FETCH_ASSOC);

if (empty($form['staff_id'])) {
    try {
        $maxIdQuery = $con->query("SELECT MAX(CAST(SUBSTRING(staff_id, 4) AS UNSIGNED)) AS max_id FROM manager WHERE staff_id LIKE 'MG-%'");
        $maxIdRow = $maxIdQuery->fetch(PDO::FETCH_ASSOC);
        $nextId = ($maxIdRow['max_id'] ?? 0) + 1;
        $form['staff_id'] = 'MG-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
        $form['staff_id'] = 'MG-001';
    }
}

$hasError = ($message !== '' || $message1 !== '');
$added = isset($_GET['added']);

$page_title = "Managers - Pharmacy Management System";
$topbar_title = "Admin Dashboard";

include '../includes/head.php';
include '../includes/sidebar_admin.php';
include '../includes/header.php';
?>

<main class="main">
    <h1>Managers</h1>
    <p class="lead">View, edit and add manager accounts.</p>

    <?php if ($added): ?>
      <div class="alert alert--ok" role="status">Manager added.</div>
    <?php endif; ?>
    <?php if ($message !== ''): ?>
      <div class="alert alert--error" role="alert"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($message1 !== ''): ?>
      <div class="alert alert--error" role="alert"><?php echo htmlspecialchars($message1); ?></div>
    <?php endif; ?>

    <div class="tabs" role="tablist" aria-label="Manager actions">
      <button type="button" class="tab" role="tab" id="tab_1" aria-controls="content_1"
              aria-selected="<?php echo $hasError ? 'false' : 'true'; ?>">View managers</button>
      <button type="button" class="tab" role="tab" id="tab_2" aria-controls="content_2"
              aria-selected="<?php echo $hasError ? 'true' : 'false'; ?>">Add manager</button>
    </div>

    <section class="panel card" id="content_1" role="tabpanel" aria-labelledby="tab_1" <?php echo $hasError ? 'hidden' : ''; ?>>
      <div class="card-head">
        <h2>All managers <span class="count">(<?php echo count($rows); ?>)</span></h2>
        <?php if (count($rows) > 0): ?>
        <div class="search">
          <i class="fas fa-search" aria-hidden="true"></i>
          <input type="search" id="filter" placeholder="Search by name or username" aria-label="Search managers">
        </div>
        <?php endif; ?>
      </div>

      <?php if (count($rows) === 0): ?>
        <div class="empty">
          <p>No managers yet. Add the first one to get started.</p>
          <button type="button" class="btn" data-go="tab_2">Add manager</button>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="people">
          <thead>
            <tr>
              <th class="num">ID</th>
              <th>Staff ID</th>
              <th>First name</th>
              <th>Last name</th>
              <th>Username</th>
              <th>Address</th>
              <th>Phone</th>
              <th class="actions">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td class="num"><?php echo htmlspecialchars($row['manager_id']); ?></td>
              <td><?php echo htmlspecialchars($row['staff_id']); ?></td>
              <td><?php echo htmlspecialchars($row['first_name']); ?></td>
              <td><?php echo htmlspecialchars($row['last_name']); ?></td>
              <td><?php echo htmlspecialchars($row['username']); ?></td>
              <td><?php echo htmlspecialchars($row['postal_address']); ?></td>
              <td><?php echo htmlspecialchars($row['phone']); ?></td>
              <td class="actions">
                <a class="icon-btn icon-btn--edit" title="Edit" aria-label="Edit <?php echo htmlspecialchars($row['username']); ?>"
                   href="update_manager.php?username=<?php echo urlencode($row['username']); ?>"><i class="fas fa-edit"></i></a>
                <a class="icon-btn icon-btn--delete" title="Delete" aria-label="Delete <?php echo htmlspecialchars($row['username']); ?>"
                   href="delete_manager.php?manager_id=<?php echo urlencode($row['manager_id']); ?>"
                   onclick="return confirm('Are you sure you want to delete this manager?');"><i class="fas fa-trash"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="no-match" id="noMatch">No managers match your search.</div>
      <?php endif; ?>
    </section>

    <section class="panel card form-card" id="content_2" role="tabpanel" aria-labelledby="tab_2" <?php echo $hasError ? '' : 'hidden'; ?>>
      <h2>Add a manager</h2>
      <p class="hint">The manager will use the username and password to sign in.</p>

      <form name="form1" action="admin_manager.php" method="post" autocomplete="off">
        <div class="grid">
          <div class="field">
            <label for="first_name">First name</label>
            <input name="first_name" id="first_name" type="text" required
                   pattern="[A-Za-z ]+" title="Letters and spaces only"
                   value="<?php echo htmlspecialchars($form['first_name']); ?>">
          </div>
          <div class="field">
            <label for="last_name">Last name</label>
            <input name="last_name" id="last_name" type="text" required
                   pattern="[A-Za-z ]+" title="Letters and spaces only"
                   value="<?php echo htmlspecialchars($form['last_name']); ?>">
          </div>
          <div class="field">
            <label for="staff_id">Staff ID (Auto-generated)</label>
            <input name="staff_id" id="staff_id" type="text" required readonly
                   style="background-color: var(--mist); color: var(--ink-soft); cursor: not-allowed;"
                   value="<?php echo htmlspecialchars($form['staff_id']); ?>">
          </div>
          <div class="field">
            <label for="phone">Phone</label>
            <input name="phone" id="phone" type="tel" required
                   value="<?php echo htmlspecialchars($form['phone']); ?>">
          </div>
          <div class="field span-2">
            <label for="email">Email</label>
            <input name="email" id="email" type="email" required
                   value="<?php echo htmlspecialchars($form['email']); ?>">
          </div>
          <div class="field span-2">
            <label for="postal_address">Address</label>
            <input name="postal_address" id="postal_address" type="text" required
                   value="<?php echo htmlspecialchars($form['postal_address']); ?>">
          </div>
          <div class="field">
            <label for="username">Username</label>
            <input name="username" id="username" type="text" required autocomplete="off"
                   value="<?php echo htmlspecialchars($form['username']); ?>">
          </div>
          <div class="field">
            <label for="password">Password</label>
            <input name="password" id="password" type="password" required autocomplete="new-password">
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" name="submit" class="btn">Add manager</button>
          <button type="button" class="btn btn--ghost" data-go="tab_1">Cancel</button>
        </div>
      </form>
    </section>
</main>

<script>
(function () {
  var tabs = [document.getElementById('tab_1'), document.getElementById('tab_2')];
  function show(id) {
    tabs.forEach(function (t) {
      var on = t.id === id;
      t.setAttribute('aria-selected', on ? 'true' : 'false');
      document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
    });
  }
  tabs.forEach(function (t) { t.addEventListener('click', function () { show(t.id); }); });
  document.querySelectorAll('[data-go]').forEach(function (b) {
    b.addEventListener('click', function () { show(b.getAttribute('data-go')); });
  });

  var filter = document.getElementById('filter');
  if (filter) {
    var rows = document.querySelectorAll('#people tbody tr');
    var noMatch = document.getElementById('noMatch');
    filter.addEventListener('input', function () {
      var q = filter.value.trim().toLowerCase();
      var shown = 0;
      rows.forEach(function (r) {
        var hit = r.textContent.toLowerCase().indexOf(q) !== -1;
        r.style.display = hit ? '' : 'none';
        if (hit) shown++;
      });
      noMatch.style.display = shown === 0 ? 'block' : 'none';
    });
  }
})();
</script>

<?php include '../includes/footer.php'; ?>