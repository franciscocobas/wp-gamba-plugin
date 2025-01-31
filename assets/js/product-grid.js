document.addEventListener("DOMContentLoaded", function () {
  console.log("Product Grids Loaded!");

  // Get both grids
  const grid1 = document.getElementById("productGrid1");
  const grid2 = document.getElementById("productGrid2");
  const prevButton = document.getElementById("prevProduct");
  const nextButton = document.getElementById("nextProduct");
  const photoCounter = document.getElementById("photoCounter");

  if (!grid1 || !grid2 || !prevButton || !nextButton || !photoCounter) {
    console.warn("Missing product grids or pagination elements!");
    return;
  }

  let productCards2 = grid2.querySelectorAll(".product-card");
  let totalProducts = productCards2.length;
  let currentIndex = 0;

  function updatePagination() {
    photoCounter.innerHTML = `Photo <span class="current-item">${currentIndex + 1}</span> of ${totalProducts}`;
    prevButton.disabled = currentIndex === 0;
    nextButton.disabled = currentIndex === totalProducts - 1;
  }

  // Initialize pagination
  updatePagination();

  prevButton.addEventListener("click", function () {
    if (currentIndex > 0) {
      productCards2[currentIndex].style.display = "none";
      currentIndex--;
      productCards2[currentIndex].style.display = "block";
      updatePagination();
    }
  });

  nextButton.addEventListener("click", function () {
    if (currentIndex < totalProducts - 1) {
      productCards2[currentIndex].style.display = "none";
      currentIndex++;
      productCards2[currentIndex].style.display = "block";
      updatePagination();
    }
  });
});
