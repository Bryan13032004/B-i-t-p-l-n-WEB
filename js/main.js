/* ================================================
   VISIO — Cửa Hàng Mắt Kính | main.js
   ================================================ */

document.addEventListener('DOMContentLoaded', () => {

  /* ── Filter tabs (sản phẩm) ── */
  const filterBtns = document.querySelectorAll('.filter-btn');
  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
    });
  });

  /* ── Nút thêm vào giỏ ── */
  const addBtns = document.querySelectorAll('.product-add');
  addBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      btn.textContent = '✓';
      btn.style.background = 'var(--gold-dark)';
      setTimeout(() => {
        btn.textContent = '+';
        btn.style.background = '';
      }, 1500);
    });
  });

});