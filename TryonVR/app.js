import { FaceLandmarker, FilesetResolver } from "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.3/+esm";

var video  = document.getElementById("video");
var canvas = document.getElementById("canvas");
var ctx    = canvas.getContext("2d");

var danhSachKinh = [
  { id: 0, name: "Kinh 1", src: "assets/kinh1.png" },
  { id: 1, name: "Kinh 2", src: "assets/kinh2.png" },
  { id: 2, name: "Kinh 3", src: "assets/kinh3.png" },
];

danhSachKinh.forEach(function(k) {
  k.img = new Image();
  k.img.src = k.src;
});

var kinhDangChon = danhSachKinh[0];
var smX = 0, smY = 0, smD = 0, smA = 0;
var faceLandmarker;
var cameraOn = false;
var stream = null;

function renderGrid() {
  var grid = document.getElementById("glassesGrid");
  grid.innerHTML = "";
  danhSachKinh.forEach(function(k) {
    var card = document.createElement("div");
    card.className = "glasses-card" + (k.id === kinhDangChon.id ? " active" : "");
    card.innerHTML = '<img src="' + k.src + '"><div class="gname">' + k.name + "</div>";
    card.onclick = function() { kinhDangChon = k; renderGrid(); };
    grid.appendChild(card);
  });
}

async function toggleCamera() {
  var btn = document.getElementById("btnCamera");
  var ph  = document.getElementById("placeholder");

  if (!cameraOn) {
    stream = await navigator.mediaDevices.getUserMedia({ video: true });
    video.srcObject = stream;
    await video.play();
    cameraOn = true;
    btn.innerText = "Tắt Camera";
    btn.classList.add("off");
    ph.style.display = "none";
    setBadge("detecting", "Đang nhận diện...");

    if (!faceLandmarker) {
      var vision = await FilesetResolver.forVisionTasks("https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.3/wasm");
      faceLandmarker = await FaceLandmarker.createFromOptions(vision, {
        baseOptions: {
          modelAssetPath: "https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task",
          delegate: "GPU"
        },
        runningMode: "VIDEO",
        numFaces: 1
      });
    }
    loop();

  } else {
    stream.getTracks().forEach(function(t) { t.stop(); });
    video.srcObject = null;
    cameraOn = false;
    btn.innerText = "Bật Camera";
    btn.classList.remove("off");
    ph.style.display = "flex";
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    setBadge("", "Chưa bật camera");
  }
}
window.toggleCamera = toggleCamera;
function loop() {
  if (!cameraOn) return;

  if (video.videoWidth > 0) {
    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;

    ctx.save();
    ctx.translate(canvas.width, 0);
    ctx.scale(-1, 1);
    ctx.drawImage(video, 0, 0);
    ctx.restore();

    if (faceLandmarker && video.readyState >= 4) {
      var result = faceLandmarker.detectForVideo(video, performance.now());
      if (result.faceLandmarks && result.faceLandmarks.length > 0) {
        var lm = result.faceLandmarks[0];
        setBadge("found", "Đã nhận diện");
        veKinh(lm);
        tinhFaceShape(lm);
      } else {
        setBadge("lost", "Không thấy mặt");
      }
    }
  }
  requestAnimationFrame(loop);
}

function veKinh(lm) {
  var W = canvas.width, H = canvas.height;

  // dao x vi canvas da mirror
  var liX = (1 - lm[133].x) * W, liY = lm[133].y * H;
  var loX = (1 - lm[33].x)  * W, loY = lm[33].y  * H;
  var riX = (1 - lm[362].x) * W, riY = lm[362].y * H;
  var roX = (1 - lm[263].x) * W, roY = lm[263].y * H;

  var lcX = (liX + loX) / 2, lcY = (liY + loY) / 2;
  var rcX = (riX + roX) / 2, rcY = (riY + roY) / 2;

  var dx = rcX - lcX, dy = rcY - lcY;
  var eyeDist = Math.sqrt(dx*dx + dy*dy);
  var angle = Math.atan2(dy, dx) + Math.PI;
  // dao x cua nose bridge
  var centerX = (1 - lm[168].x) * W;
  var centerY = lm[168].y * H;

  smX = 0.7 * smX + 0.3 * centerX;
  smY = 0.7 * smY + 0.3 * centerY;
  smD = 0.7 * smD + 0.3 * eyeDist;
  smA = 0.7 * smA + 0.3 * angle;

  var kinh = kinhDangChon.img;
  if (!kinh.complete || kinh.naturalWidth === 0) return;

  var w = smD * 2.5;
  var h = w * (kinh.naturalHeight / kinh.naturalWidth);

  ctx.save();
  ctx.translate(smX, smY);
  ctx.rotate(smA);
  ctx.drawImage(kinh, -w/2, -h * 0.3, w, h);
  ctx.restore();
}
function tinhFaceShape(lm) {
  var faceW     = Math.abs(lm[454].x - lm[234].x);
  var faceH     = Math.abs(lm[152].y - lm[10].y);
  var foreheadW = Math.abs(lm[356].x - lm[127].x);
  var jawW      = Math.abs(lm[397].x - lm[172].x);
  var ratio     = faceH / faceW;

  var shape, goiY;
  if (ratio > 1.5) {
    shape = "Dài"; goiY = "Kinh tròn";
  } else if (foreheadW / jawW > 1.3) {
    shape = "Trái tim"; goiY = "Kinh tròn";
  } else if (Math.abs(foreheadW - jawW) / faceW < 0.1 && ratio < 1.1) {
    shape = "Tròn"; goiY = "Kinh vuông, chữ nhật";
  } else if (foreheadW / faceW > 0.85 && jawW / faceW > 0.85 && ratio < 1.3) {
    shape = "Vuông"; goiY = "Kinh tròn";
  } else {
    shape = "Oval"; goiY = "Hợp với nhiều kiểu kính";
  }

  document.getElementById("faceShape").innerText = shape;
  document.getElementById("goiY").innerText = goiY;
}

function setBadge(type, text) {
  var el = document.getElementById("statusBadge");
  el.className = "badge " + type;
  el.innerText = text;
}

function chupAnh() {
  var link = document.createElement("a");
  link.download = "tryon.png";
  link.href = canvas.toDataURL("image/png");
  link.click();
}
window.chupAnh = chupAnh;

renderGrid();