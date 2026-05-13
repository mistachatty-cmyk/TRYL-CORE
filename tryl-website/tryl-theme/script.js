document.addEventListener("DOMContentLoaded", (event) => {
    gsap.registerPlugin(ScrollTrigger);

    const heroTl = gsap.timeline({ defaults: { ease: "power4.out", duration: 1.5 } });

    gsap.set(".hero-portrait", { autoAlpha: 0, scale: 1.15, filter: "blur(10px)" });
    gsap.set(".text-righteous", { yPercent: 50, autoAlpha: 0, rotationX: -45, transformOrigin: "0% 100%" });
    gsap.set(".text-yield-life", { yPercent: 50, autoAlpha: 0, rotationX: -45, transformOrigin: "0% 100%" });

    heroTl
      .to(".hero-portrait", { autoAlpha: 1, scale: 1, filter: "blur(0px)", duration: 2, ease: "power3.inOut" })
      .to(".text-righteous", { yPercent: 0, autoAlpha: 1, rotationX: 0, stagger: 0.1 }, "-=1.0")
      .to(".text-yield-life", { yPercent: 0, autoAlpha: 1, rotationX: 0, stagger: 0.1 }, "-=1.3");

    gsap.to(".hero-text-left", {
      yPercent: -30,
      ease: "none",
      scrollTrigger: {
        trigger: ".hero-section",
        start: "top top",
        end: "bottom top",
        scrub: true
      }
    });

    gsap.to(".hero-text-right", {
      yPercent: 15,
      ease: "none",
      scrollTrigger: {
        trigger: ".hero-section",
        start: "top top",
        end: "bottom top",
        scrub: true
      }
    });
});
