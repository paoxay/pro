<?php
// File: admin/manage_packages.php (เวอร์ชันปรับปรุง)
require_once 'admin_header.php';
require_once 'db_connect.php';

$game_id = isset($_GET['game_id']) ? (int)$_GET['game_id'] : 0;
if ($game_id <= 0) { die("<h2>Error: Invalid Game ID</h2>"); }

$stmt_game = $conn->prepare("SELECT name FROM games WHERE id = ?");
$stmt_game->bind_param("i", $game_id);
$stmt_game->execute();
$result_game = $stmt_game->get_result();
if ($result_game->num_rows === 0) { die("<h2>Error: Game not found</h2>"); }
$game = $result_game->fetch_assoc();
$stmt_game->close();

$stmt_packages = $conn->prepare("SELECT * FROM game_packages WHERE game_id = ? ORDER BY price ASC");
$stmt_packages->bind_param("i", $game_id);
$stmt_packages->execute();
$packages = $stmt_packages->get_result();
?>

<a href="manage_games.php">&larr; ກັບໄປໜ້າຈັດການເກມ</a>
<h2>ຈັດການແພັກເກັດສຳລັບ: <span style="color:#007BFF;"><?php echo htmlspecialchars($game['name']); ?></span></h2>

<button id="addRowBtn" style="margin-bottom: 15px;">+ ເພີ່ມແພັກເກັດໃໝ່</button>

<table id="packagesTable">
    <thead>
        <tr>
            <th>ID</th>
            <th>ຊື່ແພັກເກັດ</th>
            <th>ລາຄາຂາຍ (ກີບ)</th>
            <th>ຕົ້ນທຶນ (ກີບ)</th>
            <th>ເຄື່ອງມື</th>
        </tr>
    </thead>
    <tbody>
        <?php while($pkg = $packages->fetch_assoc()): ?>
        <tr data-id="<?php echo $pkg['id']; ?>">
            <td><?php echo $pkg['id']; ?></td>
            <td><?php echo htmlspecialchars($pkg['name']); ?></td>
            <td><?php echo number_format($pkg['price'], 2); ?></td>
            <td><?php echo number_format($pkg['cost_price'], 2); ?></td>
            <td>
                <a href="#">ແກ້ໄຂ</a>
                <a href="#" style="color:red;">ລຶບ</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<script>
// ตัวแปรสำหรับเก็บข้อมูลเดิมก่อนแก้ไข
let originalRowHTML = null;

document.getElementById('addRowBtn').addEventListener('click', function() {
    // ... (ส่วนของการเพิ่มแถวใหม่ เหมือนเดิม) ...
    const tableBody = document.querySelector('#packagesTable tbody');
    // ป้องกันการเพิ่มแถวซ้ำซ้อน
    if (document.querySelector('.new-row')) return;

    const newRow = tableBody.insertRow(0);
    newRow.classList.add('new-row');
    newRow.innerHTML = `
        <td>*</td>
        <td><input type="text" name="name" placeholder="ຊື່ແພັກເກັດ" required></td>
        <td><input type="number" name="price" placeholder="0.00" step="0.01" required></td>
        <td><input type="number" name="cost_price" placeholder="0.00" step="0.01" required></td>
        <td>
            <button class="saveBtn">ບັນທຶກ</button>
            <button class="cancelBtn" style="background-color: #ccc;">ຍົກເລີກ</button>
        </td>
    `;
});

document.querySelector('#packagesTable').addEventListener('click', function(e) {
    const target = e.target;
    const row = target.closest('tr');

    // --- จัดการการเพิ่ม (Save New) ---
    if (target.classList.contains('saveBtn') && row.classList.contains('new-row')) {
        // ... (โค้ดบันทึกของใหม่ เหมือนเดิม) ...
        const nameInput = row.querySelector('input[name="name"]');
        const priceInput = row.querySelector('input[name="price"]');
        const costInput = row.querySelector('input[name="cost_price"]');
        const data = {
            game_id: <?php echo $game_id; ?>,
            name: nameInput.value,
            price: parseFloat(priceInput.value),
            cost_price: parseFloat(costInput.value)
        };
        if (!data.name || isNaN(data.price) || isNaN(data.cost_price)) {
            alert('ກະລຸນາປ້ອນຂໍ້ມູນໃຫ້ຄົບຖ້ວນ.'); return;
        }
        fetch('ajax_add_package.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
        .then(res => res.json()).then(result => {
            if (result.success) {
                row.dataset.id = result.new_id;
                row.cells[0].innerText = result.new_id;
                row.cells[1].innerText = data.name;
                row.cells[2].innerText = data.price.toFixed(2);
                row.cells[3].innerText = data.cost_price.toFixed(2);
                row.cells[4].innerHTML = `<a href="#" class="editBtn">ແກ້ໄຂ</a> <a href="#" class="deleteBtn" style="color:red;">ລຶບ</a>`;
                row.classList.remove('new-row');
            } else { alert('Error: ' + result.message); }
        });
    }

    // --- ยกเลิกการเพิ่ม หรือ การแก้ไข ---
    if (target.classList.contains('cancelBtn')) {
        if (row.classList.contains('new-row')) {
            row.remove();
        } else {
            row.innerHTML = originalRowHTML;
            originalRowHTML = null;
        }
    }
    
    // --- จัดการการลบ (Delete) ---
    if (target.classList.contains('deleteBtn')) {
        e.preventDefault();
        if (confirm('ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລຶບແພັກເກັດນີ້?')) {
            const packageId = row.dataset.id;
            fetch('ajax_delete_package.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: packageId }) })
            .then(res => res.json()).then(result => {
                if (result.success) {
                    row.remove();
                } else { alert('Error: ' + result.message); }
            });
        }
    }

    // --- จัดการการแก้ไข (Edit) ---
    if (target.classList.contains('editBtn')) {
        e.preventDefault();
        // ถ้ากำลังแก้ไขแถวอื่นอยู่ ให้ยกเลิกก่อน
        if (originalRowHTML) {
             const editingRow = document.querySelector('tr.editing');
             if(editingRow) editingRow.innerHTML = originalRowHTML;
        }
       
        originalRowHTML = row.innerHTML;
        row.classList.add('editing');
        const id = row.dataset.id;
        const name = row.cells[1].innerText;
        const price = parseFloat(row.cells[2].innerText);
        const cost_price = parseFloat(row.cells[3].innerText);

        row.cells[1].innerHTML = `<input type="text" name="name" value="${name}">`;
        row.cells[2].innerHTML = `<input type="number" name="price" value="${price.toFixed(2)}" step="0.01">`;
        row.cells[3].innerHTML = `<input type="number" name="cost_price" value="${cost_price.toFixed(2)}" step="0.01">`;
        row.cells[4].innerHTML = `<button class="saveEditBtn">ບັນທຶກ</button> <button class="cancelBtn" style="background-color: #ccc;">ຍົກເລີກ</button>`;
    }

    // --- จัดการการบันทึกหลังแก้ไข (Save Edit) ---
    if (target.classList.contains('saveEditBtn')) {
        const packageId = row.dataset.id;
        const name = row.querySelector('input[name="name"]').value;
        const price = parseFloat(row.querySelector('input[name="price"]').value);
        const cost_price = parseFloat(row.querySelector('input[name="cost_price"]').value);

        if (!name || isNaN(price) || isNaN(cost_price)) {
            alert('ກະລຸນາປ້ອນຂໍ້ມູນໃຫ້ຄົບຖ້ວນ.'); return;
        }

        const data = { id: packageId, name: name, price: price, cost_price: cost_price };

        fetch('ajax_edit_package.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
        .then(res => res.json()).then(result => {
            if (result.success) {
                row.cells[1].innerText = name;
                row.cells[2].innerText = price.toFixed(2);
                row.cells[3].innerText = cost_price.toFixed(2);
                row.cells[4].innerHTML = `<a href="#" class="editBtn">ແກ້ໄຂ</a> <a href="#" class="deleteBtn" style="color:red;">ລຶບ</a>`;
                row.classList.remove('editing');
                originalRowHTML = null;
            } else { alert('Error: ' + result.message); }
        });
    }
});
</script>

</body>
</html>
<?php
$stmt_packages->close();
$conn->close();
?>