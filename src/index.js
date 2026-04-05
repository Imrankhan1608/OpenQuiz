// MENU BURGER
const menu = document.getElementById("menu");
const btn = document.getElementById("bouton_menu");

btn.addEventListener("click", () => {
    menu.classList.toggle("active");
    btn.classList.toggle("actif");
});

// SCROLL VERS A PROPOS
function scrollToApropos(){
    document.getElementById("apropos").scrollIntoView({behavior:"smooth"});
}

// FADE IN BODY
window.addEventListener("load", ()=>{
    document.body.classList.add("visible");
});

// pour slides
const slides = document.querySelectorAll('.slide');
let currentSlide = 0;

function showSlide(index) {
  slides.forEach((s, i) => s.classList.remove('active'));
  slides[index].classList.add('active');
}

// Démarrage
if (slides.length > 0) {
  showSlide(0);
  setInterval(() => {
    currentSlide = (currentSlide + 1) % slides.length;
    showSlide(currentSlide);
  }, 4000); // change toutes les 4s
}