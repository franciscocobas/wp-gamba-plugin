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

  let productCards1 = grid1.querySelectorAll(".product-card");
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

  // Click on a product image in Grid 1 to update Grid 2
  productCards1.forEach((card, index) => {
    card.addEventListener("click", function (e) {
      console.log("Clicked on product index:", index);
      e.preventDefault();
      // Scroll to #productGrid2 element when click on product card
      grid2.scrollIntoView({ behavior: "smooth" });

      if (index >= totalProducts) {
        console.warn("No matching product found in Grid 2 for index:", index);
        return;
      }

      // Hide the current product in Grid 2
      productCards2[currentIndex].style.display = "none";

      // Show the new product in Grid 2
      currentIndex = index;
      productCards2[currentIndex].style.display = "block";

      // Update pagination
      updatePagination();
    });
  });
});
