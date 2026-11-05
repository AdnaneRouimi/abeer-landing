/* global THREE, gsap, ScrollTrigger, JAR_VIEWER */
(function () {
    'use strict';

    function initViewer(root) {
        if (!root || root.dataset.jpInit === '1') {
            return;
        }

        var stage = root.querySelector('[data-jp-stage]');
        var container = root.querySelector('.jp-canvas');
        if (!stage || !container || !window.THREE || !window.gsap || !window.JAR_VIEWER) {
            return;
        }

        root.dataset.jpInit = '1';

        gsap.registerPlugin(ScrollTrigger);

        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // ---- Renderer ------------------------------------------------------
        var renderer;
        try {
            renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        } catch (error) {
            container.innerHTML = '<p class="jp-error">Le rendu 3D n\'est pas disponible sur cet appareil.</p>';
            return;
        }

        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        renderer.outputEncoding = THREE.sRGBEncoding;
        renderer.toneMapping = THREE.ACESFilmicToneMapping;
        renderer.toneMappingExposure = 1.08;
        container.appendChild(renderer.domElement);

        // ---- Scene & camera ------------------------------------------------
        var scene = new THREE.Scene();
        var camera = new THREE.PerspectiveCamera(32, 1, 0.1, 100);
        camera.position.set(0, 0.1, 11.5);
        camera.lookAt(0, 0, 0);

        // Studio environment so the metallic red reads correctly.
        var pmrem = new THREE.PMREMGenerator(renderer);
        if (THREE.RoomEnvironment) {
            scene.environment = pmrem.fromScene(new THREE.RoomEnvironment(), 0.04).texture;
        }

        // ---- Lights --------------------------------------------------------
        var keyLight = new THREE.DirectionalLight(0xffffff, 2.3);
        keyLight.position.set(5, 6, 5);
        scene.add(keyLight);

        var fillLight = new THREE.DirectionalLight(0xffffff, 1.0);
        fillLight.position.set(-5, 1, 3);
        scene.add(fillLight);

        var rimLight = new THREE.DirectionalLight(0xffffff, 1.7);
        rimLight.position.set(0, 3, -6);
        scene.add(rimLight);

        // ---- Model hierarchy -----------------------------------------------
        // pivot: rotated by the scroll timeline. jar: revealed / framed.
        var pivot = new THREE.Group();
        scene.add(pivot);

        var jar = new THREE.Group();
        jar.scale.setScalar(0.86);
        jar.position.y = -0.9;
        var initialRotation = parseFloat(root.getAttribute('data-initial-rotation') || '0');
        jar.rotation.y = (isFinite(initialRotation) ? initialRotation : 0) * Math.PI / 180;
        pivot.add(jar);

        var capNode = null;
        var capRestY = 0;
        var capRestQuat = null;
        var openProgress = { value: 0 };

        var yAxis = new THREE.Vector3(0, 1, 0);
        var zAxis = new THREE.Vector3(0, 0, 1);
        var tmpQuat = new THREE.Quaternion();

        // 0 = closed, 1 = fully open. Controls the lid lift + unscrew.
        function applyOpen(value) {
            openProgress.value = value;
            if (!capNode) {
                return;
            }
            capNode.position.y = capRestY + value * 34;
            tmpQuat.setFromAxisAngle(yAxis, value * Math.PI * 1.9);
            capNode.quaternion.copy(capRestQuat).multiply(tmpQuat);
            tmpQuat.setFromAxisAngle(zAxis, value * 0.14);
            capNode.quaternion.multiply(tmpQuat);
        }

        var loader = new THREE.GLTFLoader();
        loader.load(
            JAR_VIEWER.modelUrl,
            function (gltf) {
                var model = gltf.scene;

                // Normalize: center the model and scale it to a consistent size.
                var box = new THREE.Box3().setFromObject(model);
                var size = box.getSize(new THREE.Vector3());
                var maxDim = Math.max(size.x, size.y, size.z) || 1;
                var scale = 3.8 / maxDim;
                model.scale.setScalar(scale);

                var center = box.getCenter(new THREE.Vector3());
                model.position.sub(center.clone().multiplyScalar(scale));

                jar.add(model);

                capNode = model.getObjectByName('Cap_');
                if (capNode) {
                    capRestY = capNode.position.y;
                    capRestQuat = capNode.quaternion.clone();
                }

                applyOpen(openProgress.value);
                gsap.to(container, { autoAlpha: 1, duration: 0.9, ease: 'power2.out' });
            },
            undefined,
            function () {
                container.innerHTML = '<p class="jp-error">Le modèle 3D n\'a pas pu être chargé.</p>';
            }
        );

        gsap.set(container, { autoAlpha: 0 });

        // ---- Resize --------------------------------------------------------
        function resize() {
            var w = container.clientWidth;
            var h = container.clientHeight;
            if (!w || !h) {
                return;
            }
            renderer.setSize(w, h);
            var aspect = w / h;
            camera.aspect = aspect;
            // Pull the camera back on narrow screens so the whole jar stays visible.
            camera.position.z = aspect < 0.9 ? 15 : aspect < 1.2 ? 13 : 11.5;
            camera.updateProjectionMatrix();
        }
        window.addEventListener('resize', resize);
        resize();

        // Keep the canvas in sync when the Elementor editor resizes the frame.
        if (window.ResizeObserver) {
            new ResizeObserver(resize).observe(container);
        }

        // ---- Pointer parallax ----------------------------------------------
        var pointer = { x: 0, y: 0 };
        window.addEventListener('pointermove', function (event) {
            pointer.x = (event.clientX / window.innerWidth) * 2 - 1;
            pointer.y = (event.clientY / window.innerHeight) * 2 - 1;
        });

        // ---- Auto-hiding navigation ----------------------------------------
        var nav = root.querySelector('[data-jp-nav]');
        if (nav) {
            var lastScrollY = window.scrollY;
            window.addEventListener('scroll', function () {
                var currentY = window.scrollY;
                if (currentY > lastScrollY && currentY > 140) {
                    nav.classList.add('is-hidden');
                } else {
                    nav.classList.remove('is-hidden');
                }
                lastScrollY = currentY;
            }, { passive: true });
        }

        // ---- Custom trailing cursor ----------------------------------------
        var cursor = root.querySelector('.jp-cursor');
        var canHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
        if (cursor && !reduceMotion && canHover) {
            var dot = cursor.querySelector('.jp-cursor__dot');
            var ring = cursor.querySelector('.jp-cursor__ring');
            if (dot && ring) {
                cursor.classList.add('is-active');

                var dotX = gsap.quickTo(dot, 'x', { duration: 0.06, ease: 'power2.out' });
                var dotY = gsap.quickTo(dot, 'y', { duration: 0.06, ease: 'power2.out' });
                var ringX = gsap.quickTo(ring, 'x', { duration: 0.38, ease: 'power3.out' });
                var ringY = gsap.quickTo(ring, 'y', { duration: 0.38, ease: 'power3.out' });

                window.addEventListener('pointermove', function (event) {
                    dotX(event.clientX);
                    dotY(event.clientY);
                    ringX(event.clientX);
                    ringY(event.clientY);
                });

                Array.prototype.slice
                    .call(root.querySelectorAll('a, button, input, textarea, .jp-product'))
                    .forEach(function (el) {
                        el.addEventListener('pointerenter', function () {
                            ring.classList.add('is-hover');
                        });
                        el.addEventListener('pointerleave', function () {
                            ring.classList.remove('is-hover');
                        });
                    });

                window.addEventListener('pointerdown', function () {
                    ring.classList.add('is-down');
                });
                window.addEventListener('pointerup', function () {
                    ring.classList.remove('is-down');
                });

                document.addEventListener('pointerleave', function () {
                    cursor.classList.add('is-hidden');
                });
                document.addEventListener('pointerenter', function () {
                    cursor.classList.remove('is-hidden');
                });
            }
        }

        // ---- Map: click to interact (so it never traps page scrolling) ------
        var mapOverlay = root.querySelector('[data-jp-map-overlay]');
        if (mapOverlay) {
            mapOverlay.addEventListener('click', function () {
                var map = mapOverlay.parentElement;
                if (map) {
                    map.classList.add('is-active');
                }
            });
        }

        // ---- Render loop ---------------------------------------------------
        function animate(time) {
            requestAnimationFrame(animate);
            if (!reduceMotion) {
                camera.position.x += (pointer.x * 0.45 - camera.position.x) * 0.05;
                camera.position.y += (0.1 - pointer.y * 0.25 - camera.position.y) * 0.05;
                camera.lookAt(0, 0, 0);
            }
            // Subtle idle float, independent from the scroll timeline.
            pivot.position.y = reduceMotion ? 0 : Math.sin((time || 0) * 0.001) * 0.025;
            renderer.render(scene, camera);
        }
        animate();

        // ---- Scroll choreography -------------------------------------------
        var timeline = gsap.timeline({
            defaults: { ease: 'none' },
            scrollTrigger: {
                trigger: stage,
                start: 'top top',
                end: 'bottom bottom',
                scrub: 0.5,
                invalidateOnRefresh: true
            }
        });

        // Hero copy and jar reveal.
        var hero = root.querySelector('.jp-hero');
        if (hero) {
            timeline.to(hero, { autoAlpha: 0, y: -40, duration: 0.07, ease: 'power1.out' }, 0);
        }
        timeline.to(jar.scale, { x: 1, y: 1, z: 1, duration: 0.12, ease: 'power2.out' }, 0);
        timeline.to(jar.position, { y: 0, duration: 0.14, ease: 'power2.out' }, 0);

        // The jar keeps turning for the whole story.
        timeline.to(pivot.rotation, { y: Math.PI * 1.75, duration: 1, ease: 'none' }, 0);

        // Alternating info panels, driven by how many panels exist.
        var panels = Array.prototype.slice.call(root.querySelectorAll('[data-jp-panel]'));
        if (panels.length) {
            var startAt = 0.1;
            var endAt = 0.81;
            var step = (endAt - startAt) / panels.length;
            panels.forEach(function (panel, index) {
                var start = startAt + step * index;
                var end = start + step;
                var fade = Math.min(0.04, step * 0.25);
                timeline.fromTo(
                    panel,
                    { autoAlpha: 0, y: 46 },
                    { autoAlpha: 1, y: 0, duration: fade, ease: 'power2.out' },
                    start
                );
                timeline.to(
                    panel,
                    { autoAlpha: 0, y: -46, duration: fade, ease: 'power2.in' },
                    end - fade
                );
            });
        }

        // Lid opens early, as the jar starts spinning, then closes at the end.
        timeline.to(
            openProgress,
            {
                value: 1,
                duration: 0.2,
                ease: 'power1.inOut',
                onUpdate: function () {
                    applyOpen(openProgress.value);
                }
            },
            0.3
        );
        timeline.to(
            openProgress,
            {
                value: 0,
                duration: 0.17,
                ease: 'power1.inOut',
                onUpdate: function () {
                    applyOpen(openProgress.value);
                }
            },
            0.82
        );

        // ---- Products entrance ---------------------------------------------
        if (!reduceMotion) {
            Array.prototype.slice
                .call(root.querySelectorAll('.jp-product, .jp-products__actions, .jp-products__lead, .jp-products__title'))
                .forEach(function (el) {
                    gsap.from(el, {
                        autoAlpha: 0,
                        y: 40,
                        duration: 0.8,
                        ease: 'power2.out',
                        scrollTrigger: {
                            trigger: el,
                            start: 'top 92%',
                            once: true
                        }
                    });
                });
        }
    }

    // Initialize every viewer on the page, including ones Elementor renders
    // after the initial page load (the editor replaces widget markup via AJAX).
    function boot() {
        Array.prototype.slice.call(document.querySelectorAll('.jp-viewer')).forEach(initViewer);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    if (window.MutationObserver) {
        new MutationObserver(boot).observe(document.documentElement, {
            childList: true,
            subtree: true
        });
    }
})();
