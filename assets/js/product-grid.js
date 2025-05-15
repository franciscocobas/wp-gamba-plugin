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
    photoCounter.innerHTML = `Foto <span class="current-item">${currentIndex + 1}</span> de ${totalProducts}`;
    prevButton.disabled = currentIndex === 0;
    nextButton.disabled = currentIndex === totalProducts - 1;
    const productGrid2 = document.querySelector("#productGrid2").offsetHeight;
    const paginationContainer = document.querySelector(".pagination-container").offsetHeight;
    const rightContainer = productGrid2 + paginationContainer;
    document.querySelector("#productGrid1").style.maxHeight = rightContainer + "px";
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

  function openPopup(e) {
    const popupId = this.getAttribute("data-popup");
    const popup = document.getElementById(popupId);

    if (popup) {
      popup.classList.remove("hidden");
      document.getElementById("holis").textContent = popup.style.display
    }
  }

  // Abrir popup
  document.querySelectorAll(".share-photo").forEach(function (button) {
    button.addEventListener("click", openPopup);
  });

  // Cerrar popup
  document.querySelectorAll(".share-popup-2 .close").forEach(function (closeBtn) {
    closeBtn.addEventListener("click", function () {
      const popup = this.closest(".share-popup-2");
      popup.style.display = "none";
      popup.querySelector(".copy-message").style.display = "none";
    });
  });

  // Cerrar al hacer clic fuera del popup
  window.addEventListener("click", function (e) {
    document.querySelectorAll(".share-popup-2").forEach(function (popup) {
      if (e.target === popup) {
        popup.style.display = "none";
        popup.querySelector(".copy-message").style.display = "none";
      }
    });
  });

  // Copiar enlace
  document.querySelectorAll(".copy-link-btn").forEach(function (btn) {
    btn.addEventListener("click", function () {
      const link = this.getAttribute("data-link");
      navigator.clipboard.writeText(link).then(() => {
        const message = this.closest(".share-popup-content").querySelector(".copy-message");
        message.style.display = "block";

        setTimeout(() => {
          message.style.display = "none";
        }, 2000);
      });
    });
  });

  // Cerrar popup si se hace clic fuera del contenido
  window.addEventListener("click", function (e) {
    document.querySelectorAll(".share-popup-2").forEach(function (popup) {
      const content = document.querySelector(".share-actions");
      if (e.target === popup || !content.contains(e.target)) {
        popup.style.display = "none";
        const message = popup.querySelector(".copy-message");
        if (message) message.style.display = "none";
      }
    });
  });
});
