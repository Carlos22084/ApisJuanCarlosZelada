import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { RoundedBoxGeometry } from 'three/addons/geometries/RoundedBoxGeometry.js';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';

const container = document.querySelector('#furniture3d');

if (container) {
    const loading = document.querySelector('#furniture3dLoading');

    try {
        const scene = new THREE.Scene();
        scene.background = new THREE.Color(0xeee2d5);
        scene.fog = new THREE.Fog(0xeee2d5, 12, 24);

        const camera = new THREE.PerspectiveCamera(34, 1, 0.1, 50);
        camera.position.set(6.3, 4.25, 7.5);

        const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false, powerPreference: 'high-performance' });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        renderer.shadowMap.enabled = true;
        renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        renderer.outputColorSpace = THREE.SRGBColorSpace;
        renderer.toneMapping = THREE.ACESFilmicToneMapping;
        renderer.toneMappingExposure = 1.05;
        container.appendChild(renderer.domElement);

        const environmentGenerator = new THREE.PMREMGenerator(renderer);
        scene.environment = environmentGenerator.fromScene(new RoomEnvironment(), 0.04).texture;
        environmentGenerator.dispose();

        const controls = new OrbitControls(camera, renderer.domElement);
        controls.enableDamping = true;
        controls.dampingFactor = 0.06;
        controls.enablePan = false;
        controls.minDistance = 5.2;
        controls.maxDistance = 12;
        controls.minPolarAngle = Math.PI * 0.19;
        controls.maxPolarAngle = Math.PI * 0.47;
        controls.target.set(0, 1.45, 0);

        const hemiLight = new THREE.HemisphereLight(0xfff7ee, 0x755f50, 2.1);
        scene.add(hemiLight);

        const keyLight = new THREE.DirectionalLight(0xffe6ce, 4.8);
        keyLight.position.set(5.5, 8, 5);
        keyLight.castShadow = true;
        keyLight.shadow.mapSize.set(2048, 2048);
        keyLight.shadow.camera.left = -7;
        keyLight.shadow.camera.right = 7;
        keyLight.shadow.camera.top = 7;
        keyLight.shadow.camera.bottom = -4;
        keyLight.shadow.bias = -0.0003;
        scene.add(keyLight);

        const fillLight = new THREE.DirectionalLight(0xdbe8ff, 2.2);
        fillLight.position.set(-5, 4, 2);
        scene.add(fillLight);

        const rimLight = new THREE.SpotLight(0xffd5ad, 45, 16, Math.PI / 5, 0.65, 1.4);
        rimLight.position.set(-4, 6, -5);
        rimLight.target.position.set(0, 1.5, 0);
        scene.add(rimLight, rimLight.target);

        const floorMaterial = new THREE.MeshStandardMaterial({ color: 0xbda58f, roughness: 0.82, metalness: 0 });
        const floor = new THREE.Mesh(new THREE.PlaneGeometry(30, 30), floorMaterial);
        floor.rotation.x = -Math.PI / 2;
        floor.receiveShadow = true;
        scene.add(floor);

        const rug = new THREE.Mesh(
            new RoundedBoxGeometry(7.7, 0.025, 5.1, 5, 0.12),
            new THREE.MeshStandardMaterial({ color: 0xd9c7b5, roughness: 1 })
        );
        rug.position.set(0, 0.018, 0.35);
        rug.receiveShadow = true;
        scene.add(rug);

        const wall = new THREE.Mesh(
            new THREE.PlaneGeometry(24, 11),
            new THREE.MeshStandardMaterial({ color: 0xeaded2, roughness: 0.95 })
        );
        wall.position.set(0, 5.3, -5.5);
        wall.receiveShadow = true;
        scene.add(wall);

        const model = new THREE.Group();
        scene.add(model);

        let fabricMap;
        let bumpMap;
        const fabricMaterial = new THREE.MeshPhysicalMaterial({
            color: 0x9b6547,
            roughness: 0.78,
            metalness: 0,
            sheen: 0.42,
            sheenRoughness: 0.72,
            sheenColor: new THREE.Color(0xffffff),
            clearcoat: 0.02,
        });
        const seamMaterial = new THREE.MeshStandardMaterial({ color: 0x684331, roughness: 0.9 });
        const darkLiningMaterial = new THREE.MeshStandardMaterial({ color: 0x35271f, roughness: 0.9 });
        const woodMaterial = new THREE.MeshPhysicalMaterial({ color: 0x5a321d, roughness: 0.34, clearcoat: 0.32, clearcoatRoughness: 0.38 });
        const metalMaterial = new THREE.MeshStandardMaterial({ color: 0x373738, roughness: 0.3, metalness: 0.85 });

        function woodTexture() {
            const canvas = document.createElement('canvas');
            canvas.width = canvas.height = 512;
            const context = canvas.getContext('2d');
            const gradient = context.createLinearGradient(0, 0, 512, 0);
            gradient.addColorStop(0, '#8d4a18');
            gradient.addColorStop(0.22, '#c8792d');
            gradient.addColorStop(0.5, '#9f571d');
            gradient.addColorStop(0.76, '#d08a3a');
            gradient.addColorStop(1, '#7b3c12');
            context.fillStyle = gradient;
            context.fillRect(0, 0, 512, 512);

            for (let line = 0; line < 70; line++) {
                const y = line * 7.5 + Math.sin(line * 1.7) * 3;
                context.beginPath();
                context.moveTo(0, y);
                for (let x = 0; x <= 512; x += 12) {
                    context.lineTo(x, y + Math.sin(x * 0.035 + line) * (1.3 + line % 4));
                }
                context.strokeStyle = line % 3 === 0 ? 'rgba(67,27,5,.28)' : 'rgba(255,205,117,.16)';
                context.lineWidth = line % 5 === 0 ? 2 : 0.8;
                context.stroke();
            }

            const texture = new THREE.CanvasTexture(canvas);
            texture.wrapS = texture.wrapT = THREE.RepeatWrapping;
            texture.repeat.set(1.4, 4.5);
            texture.colorSpace = THREE.SRGBColorSpace;
            texture.anisotropy = Math.min(8, renderer.capabilities.getMaxAnisotropy());
            return texture;
        }

        const chairWoodMap = woodTexture();
        const chairWoodMaterial = new THREE.MeshPhysicalMaterial({
            color: 0xd18435,
            map: chairWoodMap,
            roughness: 0.38,
            clearcoat: 0.36,
            clearcoatRoughness: 0.3,
        });

        function textileTexture(kind, bump = false) {
            const canvas = document.createElement('canvas');
            canvas.width = canvas.height = 256;
            const context = canvas.getContext('2d');
            context.fillStyle = bump ? '#8f8f8f' : '#d8d8d8';
            context.fillRect(0, 0, 256, 256);

            if (kind === 'leather') {
                for (let index = 0; index < 900; index++) {
                    const x = (index * 71) % 256;
                    const y = (index * 131) % 256;
                    const radius = 0.4 + ((index * 17) % 15) / 10;
                    context.strokeStyle = bump ? 'rgba(45,45,45,.24)' : 'rgba(70,70,70,.08)';
                    context.lineWidth = 0.6;
                    context.beginPath();
                    context.arc(x, y, radius, 0, Math.PI * 1.35);
                    context.stroke();
                }
            } else if (kind === 'velvet' || kind === 'microfiber') {
                const gradient = context.createLinearGradient(0, 0, 256, 256);
                gradient.addColorStop(0, bump ? '#929292' : '#eeeeee');
                gradient.addColorStop(0.45, bump ? '#777777' : '#bababa');
                gradient.addColorStop(1, bump ? '#a1a1a1' : '#e2e2e2');
                context.fillStyle = gradient;
                context.fillRect(0, 0, 256, 256);
                for (let x = 0; x < 256; x += kind === 'microfiber' ? 4 : 2) {
                    context.fillStyle = `rgba(255,255,255,${x % 8 === 0 ? 0.08 : 0.025})`;
                    context.fillRect(x, 0, 1, 256);
                }
            } else if (kind === 'boucle') {
                for (let index = 0; index < 1450; index++) {
                    const x = (index * 47) % 260;
                    const y = (index * 83) % 260;
                    const radius = 1.2 + (index % 4) * 0.55;
                    context.strokeStyle = bump ? 'rgba(35,35,35,.34)' : 'rgba(255,255,255,.34)';
                    context.lineWidth = 1.1;
                    context.beginPath();
                    context.arc(x, y, radius, 0, Math.PI * 1.7);
                    context.stroke();
                }
            } else if (kind === 'jacquard') {
                for (let y = -32; y < 288; y += 32) {
                    for (let x = -32; x < 288; x += 32) {
                        context.fillStyle = bump ? 'rgba(45,45,45,.28)' : 'rgba(255,255,255,.22)';
                        context.beginPath();
                        context.moveTo(x, y + 16);
                        context.lineTo(x + 16, y);
                        context.lineTo(x + 32, y + 16);
                        context.lineTo(x + 16, y + 32);
                        context.closePath();
                        context.fill();
                    }
                }
            } else {
                const spacing = kind === 'linen' ? 5 : kind === 'polyester' ? 3 : 4;
                for (let line = 0; line < 256; line += spacing) {
                    context.strokeStyle = bump ? 'rgba(45,45,45,.28)' : 'rgba(255,255,255,.30)';
                    context.lineWidth = 1;
                    context.beginPath();
                    context.moveTo(line, 0);
                    context.lineTo(line, 256);
                    context.stroke();
                    context.strokeStyle = bump ? 'rgba(225,225,225,.22)' : 'rgba(55,55,55,.12)';
                    context.beginPath();
                    context.moveTo(0, line);
                    context.lineTo(256, line);
                    context.stroke();
                }
            }

            const texture = new THREE.CanvasTexture(canvas);
            texture.wrapS = texture.wrapT = THREE.RepeatWrapping;
            const repeat = kind === 'leather' ? 2.8 : kind === 'jacquard' ? 3.2 : kind === 'boucle' ? 4.2 : 5.5;
            texture.repeat.set(repeat, repeat);
            texture.anisotropy = Math.min(8, renderer.capabilities.getMaxAnisotropy());
            if (!bump) texture.colorSpace = THREE.SRGBColorSpace;
            return texture;
        }

        function updateFabric(hex = '#9b6547', kind = 'linen') {
            fabricMap?.dispose();
            bumpMap?.dispose();
            fabricMap = textileTexture(kind, false);
            bumpMap = textileTexture(kind, true);
            const color = new THREE.Color(hex);
            const seamColor = color.clone();
            seamColor.offsetHSL(0, 0.03, -0.18);

            fabricMaterial.color.copy(color);
            fabricMaterial.map = fabricMap;
            fabricMaterial.bumpMap = bumpMap;
            fabricMaterial.bumpScale = kind === 'leather' ? 0.018 : kind === 'boucle' ? 0.065 : 0.035;
            fabricMaterial.roughness = kind === 'leather' ? 0.46 : kind === 'velvet' || kind === 'microfiber' ? 0.87 : kind === 'boucle' ? 0.94 : 0.78;
            fabricMaterial.clearcoat = kind === 'leather' ? 0.18 : 0.015;
            fabricMaterial.sheen = kind === 'velvet' ? 0.8 : kind === 'microfiber' ? 0.62 : 0.34;
            fabricMaterial.needsUpdate = true;
            seamMaterial.color.copy(seamColor);
        }

        function colorComponents(hex) {
            const normalized = String(hex).replace('#', '').padEnd(6, '0').slice(0, 6);
            const value = Number.parseInt(normalized, 16);
            return { r: value >> 16, g: (value >> 8) & 255, b: value & 255 };
        }

        function componentHex(value) {
            return Math.max(0, Math.min(255, Math.round(value))).toString(16).padStart(2, '0');
        }

        function updateFabricFromAi(image) {
            if (!image?.naturalWidth || !image?.naturalHeight) return;

            try {
                const canvas = document.createElement('canvas');
                canvas.width = canvas.height = 160;
                const context = canvas.getContext('2d', { willReadFrequently: true });
                context.drawImage(image, 0, 0, canvas.width, canvas.height);
                const pixels = context.getImageData(0, 0, canvas.width, canvas.height).data;
                const material = selectedMaterial();
                const target = colorComponents(material.hex);
                const candidates = [];

                // Se prioriza el centro, donde normalmente se encuentra el tapizado generado.
                for (let y = 18; y < 150; y += 2) {
                    for (let x = 12; x < 148; x += 2) {
                        const index = (y * canvas.width + x) * 4;
                        const r = pixels[index], g = pixels[index + 1], b = pixels[index + 2];
                        const brightness = (r + g + b) / 3;
                        if (brightness < 5 || brightness > 250 || pixels[index + 3] < 230) continue;
                        const distance = Math.hypot(r - target.r, g - target.g, b - target.b);
                        candidates.push({ r, g, b, distance });
                    }
                }

                candidates.sort((left, right) => left.distance - right.distance);
                const chosen = candidates.slice(0, Math.max(80, Math.floor(candidates.length * 0.16)));
                if (!chosen.length) return;

                let red = 0, green = 0, blue = 0, totalWeight = 0;
                chosen.forEach((pixel) => {
                    const weight = 1 / (1 + pixel.distance * 0.035);
                    red += pixel.r * weight;
                    green += pixel.g * weight;
                    blue += pixel.b * weight;
                    totalWeight += weight;
                });

                const generatedHex = `#${componentHex(red / totalWeight)}${componentHex(green / totalWeight)}${componentHex(blue / totalWeight)}`;
                updateFabric(generatedHex, material.kind);
                container.dataset.aiMaterial = 'true';
            } catch (error) {
                // Si el navegador impide leer la imagen, se conserva el material seleccionado.
                console.warn('No se pudo extraer el color del diseño generado.', error);
            }
        }

        function rounded(width, height, depth, position, radius = 0.12, material = fabricMaterial) {
            const geometry = new RoundedBoxGeometry(width, height, depth, 6, Math.min(radius, width / 3, height / 3, depth / 3));
            geometry.computeVertexNormals();
            const mesh = new THREE.Mesh(geometry, material);
            mesh.position.set(...position);
            mesh.castShadow = true;
            mesh.receiveShadow = true;
            model.add(mesh);
            return mesh;
        }

        function piping(width, position, rotationX = 0) {
            const seam = rounded(width, 0.035, 0.045, position, 0.016, seamMaterial);
            seam.rotation.x = rotationX;
            return seam;
        }

        function clearModel() {
            while (model.children.length) {
                const child = model.children.pop();
                child.geometry?.dispose();
            }
        }

        function furnitureDimensions() {
            const type = (document.querySelector('#furniture_type')?.value || '').toLocaleLowerCase('es');
            if (type.includes('silla') && !type.includes('sillón')) return { kind: 'chair', label: 'Silla de madera tapizada' };
            if (type.includes('individual') || type.includes('sillón')) return { kind: 'sofa', width: 2.35, seats: 1, label: 'Sillón individual' };
            if (type.includes('dos') || type.includes('2 ') || type.includes('love')) return { kind: 'sofa', width: 3.65, seats: 2, label: 'Sofá de dos cuerpos' };
            if (type.includes('cuatro') || type.includes('4 ')) return { kind: 'sofa', width: 5.85, seats: 4, label: 'Sofá de cuatro cuerpos' };
            return { kind: 'sofa', width: 4.85, seats: 3, label: 'Sofá de tres cuerpos' };
        }

        function chairPost(x, z, height, y, tilt = 0) {
            const post = rounded(0.16, height, 0.18, [x, y, z], 0.045, chairWoodMaterial);
            post.rotation.x = tilt;
            return post;
        }

        function chairRail(width, height, depth, position, rotationX = 0) {
            const rail = rounded(width, height, depth, position, 0.045, chairWoodMaterial);
            rail.rotation.x = rotationX;
            return rail;
        }

        function curvedChairRail(y, z, bend = 0.12) {
            const curve = new THREE.QuadraticBezierCurve3(
                new THREE.Vector3(-0.72, y - bend, z),
                new THREE.Vector3(0, y + bend, z - 0.01),
                new THREE.Vector3(0.72, y - bend, z)
            );
            const rail = new THREE.Mesh(new THREE.TubeGeometry(curve, 30, 0.075, 10, false), chairWoodMaterial);
            rail.castShadow = rail.receiveShadow = true;
            rail.rotation.x = -0.105;
            model.add(rail);
            return rail;
        }

        function buildChair(style) {
            const backTilt = -0.105;

            // Cuatro patas y dos postes posteriores altos como en la silla de referencia.
            for (const x of [-0.69, 0.69]) {
                const frontLeg = chairPost(x, 0.56, 1.48, 0.76);
                frontLeg.rotation.z = x < 0 ? -0.025 : 0.025;
                chairPost(x, -0.55, 3.72, 1.86, backTilt);
            }

            // Bastidor de madera bajo el asiento y travesaños de refuerzo visibles.
            chairRail(1.58, 0.2, 0.16, [0, 1.42, 0.56]);
            chairRail(1.58, 0.2, 0.16, [0, 1.42, -0.55]);
            chairRail(0.16, 0.18, 1.12, [-0.7, 1.42, 0]);
            chairRail(0.16, 0.18, 1.12, [0.7, 1.42, 0]);
            chairRail(1.34, 0.11, 0.11, [0, 0.72, 0.58]);
            chairRail(1.34, 0.11, 0.11, [0, 0.73, -0.54]);
            chairRail(0.11, 0.11, 0.93, [-0.69, 0.76, 0.01]);
            chairRail(0.11, 0.11, 0.93, [0.69, 0.76, 0.01]);

            // Cojín oscuro, ligeramente ancho al frente y con bordes suaves.
            const seat = rounded(1.72, 0.27, 1.36, [0, 1.58, 0.04], 0.13);
            seat.rotation.x = -0.025;
            piping(1.55, [0, 1.48, 0.69]);

            // Respaldo alto tapizado, marco y doble listón curvo superior.
            const backPanel = rounded(1.22, 1.28, 0.2, [0, 2.62, -0.67], 0.1);
            backPanel.rotation.x = backTilt;
            chairRail(0.12, 1.44, 0.16, [-0.68, 2.64, -0.65], backTilt);
            chairRail(0.12, 1.44, 0.16, [0.68, 2.64, -0.65], backTilt);
            curvedChairRail(3.38, -0.72, 0.12);
            curvedChairRail(3.62, -0.76, 0.15);

            model.position.y = 0.01;
            model.scale.setScalar(0.9);
            controls.target.set(0, 1.72, 0);
            container.setAttribute('aria-label', 'Modelo tridimensional interactivo de una silla de madera tapizada');
            document.querySelector('#furniture3dLabel').textContent = `Silla de madera tapizada · estilo ${style.toLocaleLowerCase('es')}`;
        }

        function buildFurniture() {
            clearModel();
            model.position.set(0, 0, 0);
            model.scale.setScalar(1);
            const dimensions = furnitureDimensions();
            const style = document.querySelector('#design_style')?.value || 'Contemporáneo';
            if (dimensions.kind === 'chair') {
                buildChair(style);
                return;
            }

            const { width, seats, label } = dimensions;
            const classic = style === 'Clásico' || style === 'Capitoné';
            const minimalist = style === 'Minimalista';
            const rustic = style === 'Rústico';
            const armWidth = minimalist ? 0.24 : classic ? 0.5 : 0.42;
            const innerWidth = width - armWidth * 2 - 0.12;
            const seatWidth = innerWidth / seats;

            rounded(width - 0.18, 0.58, 1.82, [0, 0.91, 0], 0.17);
            rounded(width - 0.36, 1.55, 0.42, [0, 1.94, -0.72], classic ? 0.23 : 0.15);
            rounded(width - 0.28, 0.16, 1.68, [0, 1.23, 0.03], 0.06, darkLiningMaterial);

            for (let side of [-1, 1]) {
                const x = side * (width / 2 - armWidth / 2);
                if (classic) {
                    const arm = new THREE.Mesh(new THREE.CapsuleGeometry(0.34, 1.26, 8, 20), fabricMaterial);
                    arm.rotation.x = Math.PI / 2;
                    arm.position.set(x, 1.62, 0.06);
                    arm.castShadow = arm.receiveShadow = true;
                    model.add(arm);
                } else {
                    rounded(armWidth, minimalist ? 1.02 : 1.26, 1.92, [x, minimalist ? 1.5 : 1.61, 0.03], minimalist ? 0.08 : 0.2);
                }
            }

            for (let index = 0; index < seats; index++) {
                const x = -innerWidth / 2 + seatWidth / 2 + index * seatWidth;
                const cushionWidth = seatWidth - 0.055;
                const seat = rounded(cushionWidth, 0.34, 1.52, [x, 1.43, 0.12], 0.15);
                seat.rotation.x = -0.018;
                piping(cushionWidth - 0.1, [x, 1.32, 0.89]);

                const back = rounded(cushionWidth - 0.05, 1.15, 0.38, [x, 2.12, -0.39], 0.18);
                back.rotation.x = -0.095;
                piping(cushionWidth - 0.14, [x, 2.62, -0.315], -0.095);

                if (style === 'Capitoné') {
                    for (const offsetX of [-0.22, 0.22]) {
                        for (const offsetY of [-0.23, 0.18]) {
                            const button = new THREE.Mesh(new THREE.SphereGeometry(0.045, 16, 12), seamMaterial);
                            button.position.set(x + offsetX * Math.min(1, cushionWidth), 2.13 + offsetY, -0.175);
                            button.castShadow = true;
                            model.add(button);
                        }
                    }
                }
            }

            const legMaterial = minimalist ? metalMaterial : woodMaterial;
            for (const x of [-width / 2 + 0.48, width / 2 - 0.48]) {
                for (const z of [-0.58, 0.58]) {
                    const leg = new THREE.Mesh(new THREE.CylinderGeometry(0.095, 0.145, 0.62, 18), legMaterial);
                    leg.position.set(x, 0.38, z);
                    leg.rotation.z = x < 0 ? 0.075 : -0.075;
                    leg.rotation.x = z < 0 ? -0.055 : 0.055;
                    leg.castShadow = true;
                    model.add(leg);
                }
            }

            if (rustic) {
                rounded(width - 0.55, 0.1, 0.12, [0, 0.62, 0.77], 0.025, woodMaterial);
            }

            const pillowSize = seats === 1 ? 0.47 : 0.42;
            for (const side of [-1, 1]) {
                if (seats === 1 && side === 1) continue;
                const pillow = rounded(pillowSize, pillowSize * 1.12, 0.18, [side * (innerWidth / 2 - pillowSize * 0.55), 1.92, -0.02], 0.1);
                pillow.rotation.z = side * -0.16;
                pillow.rotation.x = -0.14;
            }

            model.position.y = 0.01;
            controls.target.set(0, 1.45, 0);
            container.setAttribute('aria-label', `Modelo tridimensional interactivo de ${label.toLocaleLowerCase('es')}`);
            document.querySelector('#furniture3dLabel').textContent = `${label} · estilo ${style.toLocaleLowerCase('es')}`;
        }

        function selectedMaterial() {
            const select = document.querySelector('#material_id');
            const option = select?.options[select.selectedIndex];
            return {
                hex: option?.dataset.hex || '#9b6547',
                kind: option?.dataset.texture || 'linen',
            };
        }

        function resetCamera() {
            camera.position.set(6.3, 4.25, 7.5);
            controls.target.set(0, furnitureDimensions().kind === 'chair' ? 1.72 : 1.45, 0);
            controls.update();
        }

        document.querySelector('#material_id')?.addEventListener('change', () => {
            const material = selectedMaterial();
            delete container.dataset.aiMaterial;
            updateFabric(material.hex, material.kind);
        });
        document.querySelectorAll('.color-choice[data-hex]').forEach((button) => button.addEventListener('click', () => {
            delete container.dataset.aiMaterial;
            updateFabric(button.dataset.hex, selectedMaterial().kind);
        }));
        window.addEventListener('ai-design-ready', (event) => updateFabricFromAi(event.detail?.image));
        document.querySelector('#design_style')?.addEventListener('change', buildFurniture);

        let furnitureTimer;
        document.querySelector('#furniture_type')?.addEventListener('change', () => {
            clearTimeout(furnitureTimer);
            furnitureTimer = setTimeout(buildFurniture, 80);
        });
        document.querySelector('#reset3dView')?.addEventListener('click', resetCamera);
        document.querySelector('#toggle3dRotation')?.addEventListener('click', (event) => {
            controls.autoRotate = !controls.autoRotate;
            controls.autoRotateSpeed = 1.2;
            event.currentTarget.setAttribute('aria-pressed', String(controls.autoRotate));
        });

        const resize = () => {
            const width = Math.max(1, container.clientWidth);
            const height = Math.max(1, container.clientHeight);
            renderer.setSize(width, height, false);
            camera.aspect = width / height;
            camera.updateProjectionMatrix();
        };
        new ResizeObserver(resize).observe(container);
        resize();

        const initialMaterial = selectedMaterial();
        updateFabric(initialMaterial.hex, initialMaterial.kind);
        buildFurniture();

        renderer.setAnimationLoop(() => {
            controls.update();
            renderer.render(scene, camera);
        });

        requestAnimationFrame(() => loading?.classList.add('ready'));
    } catch (error) {
        console.error('No fue posible iniciar el modelo 3D local.', error);
        if (loading) loading.remove();
        container.innerHTML = '<div class="furniture-3d-fallback"><strong>Tu navegador no pudo mostrar el modelo 3D.</strong><br>Actualiza el navegador o activa la aceleración gráfica.</div>';
    }
}
