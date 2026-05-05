/* ================================================
   VISIO — Cửa Hàng Mắt Kính | main.js
   ================================================ */

document.addEventListener('DOMContentLoaded', () => {

  // Filter sản phẩm theo category 
  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const cat = btn.dataset.cat;
      document.querySelectorAll('.product-card').forEach(card => {
        if (!cat || cat === 'all' || card.dataset.cat === cat) {
          card.style.display = 'block';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });

  //Nút thêm vào giỏ 
  document.querySelectorAll('.product-add').forEach(btn => {
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