// Import the functions you need from the SDKs you need
import { initializeApp } from "firebase/app";
import { getAnalytics } from "firebase/analytics";
// TODO: Add SDKs for Firebase products that you want to use
// https://firebase.google.com/docs/web/setup#available-libraries

// Your web app's Firebase configuration
// For Firebase JS SDK v7.20.0 and later, measurementId is optional
const firebaseConfig = {
  apiKey: "AIzaSyAtW6VKIo8e-kOWoUlbhV1B1xdMmshR4qY",
  authDomain: "wireshoes-f1b39.firebaseapp.com",
  projectId: "wireshoes-f1b39",
  storageBucket: "wireshoes-f1b39.firebasestorage.app",
  messagingSenderId: "1052631381277",
  appId: "1:1052631381277:web:7a646e5dd4435a6aa2ddf4",
  measurementId: "G-KKZL4921W8"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const analytics = getAnalytics(app);