import cv2
import mediapipe as mp
from pynput.keyboard import Controller, Key
import numpy as np
import time

keyboard = Controller()
mp_face_mesh = mp.solutions.face_mesh
face_mesh = mp_face_mesh.FaceMesh(refine_landmarks=True)

# Eye landmark indexes for EAR calculation
LEFT_EYE = [362, 385, 387, 263, 373, 380]
RIGHT_EYE = [33, 160, 158, 133, 153, 144]
EYE_CLOSED_THRESHOLD = 0.20

last_action_time = 0
cooldown = 0.8  # seconds

def get_ear(landmarks, eye_indices, image_width, image_height):
    points = [landmarks[i] for i in eye_indices]
    coords = [(int(p.x * image_width), int(p.y * image_height)) for p in points]
    A = np.linalg.norm(np.array(coords[1]) - np.array(coords[5]))
    B = np.linalg.norm(np.array(coords[2]) - np.array(coords[4]))
    C = np.linalg.norm(np.array(coords[0]) - np.array(coords[3]))
    ear = (A + B) / (2.0 * C)
    return ear

cap = cv2.VideoCapture(0)
print("Press ESC to exit")

while cap.isOpened():
    success, image = cap.read()
    if not success:
        break

    image = cv2.flip(image, 1)
    rgb_image = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
    results = face_mesh.process(rgb_image)
    image_height, image_width, _ = image.shape

    if results.multi_face_landmarks:
        landmarks = results.multi_face_landmarks[0].landmark

        left_ear = get_ear(landmarks, LEFT_EYE, image_width, image_height)
        right_ear = get_ear(landmarks, RIGHT_EYE, image_width, image_height)

        left_eye_closed = left_ear < EYE_CLOSED_THRESHOLD
        right_eye_closed = right_ear < EYE_CLOSED_THRESHOLD

        current_time = time.time()

        if current_time - last_action_time > cooldown:
            if left_eye_closed and right_eye_closed:
                print("Both eyes closed - No action")
                last_action_time = current_time
            elif left_eye_closed and not right_eye_closed:
                keyboard.press(Key.right)
                keyboard.release(Key.right)
                print("Left eye closed - Right arrow pressed")
                last_action_time = current_time
            elif right_eye_closed and not left_eye_closed:
                keyboard.press(Key.left)
                keyboard.release(Key.left)
                print("Right eye closed - Left arrow pressed")
                last_action_time = current_time
            else:
                print("Eyes open - No action")

    cv2.imshow("Eye Control", image)

    if cv2.waitKey(5) & 0xFF == 27:
        break

cap.release()
cv2.destroyAllWindows()
