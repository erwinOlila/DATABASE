# Dynamic Receiver for Message Stream via MQTT
# @reboot /usr/bin/python3 script.py
# Erwin & Don
# University of San Carlos - TC

import sys
import os
import paho.mqtt.client as mqtt
import subprocess

logger = "/home/pi/don/logger.php"
checker = "/home/pi/don/checker.php"

def on_connect(client, userdata, flags, rc):
    print("Connected with result code" +str(rc))
    #client.subscribe("bunzel/LB344/occupancy")

def on_message(client, userdata, msg):
    allData = msg.payload.decode('utf-8')
    status_raw = msg.topic
    status = status_raw.split("/")
    room = status[1]
    message_type = status[2]
    if(allData != "turn me off please!"):
        if(message_type == "STA"):
            getStatus(room, allData)
        elif(message_type == "OCC"):
            getOccupant(room, allData)

def getStatus(room, msg):
    status = msg[5:21]
    os.system("php "+logger+" %s" % (room+status))

def getOccupant(room, msg):
    occ = msg[5:8]
    if(occ == "OUT"):
        teacher = "0000"
    else:
        teacher = msg[5:13]
    os.system("/usr/bin/php "+checker+" %s %s" % (room,teacher))

client = mqtt.Client()
client.reinitialise(client_id="", clean_session=True, userdata=None)
client.on_connect = on_connect
client.on_message = on_message
client.connect("172.24.1.1", 1883, 60)
client.subscribe("bunzel/#")

while True:

    try:

        client.loop_start()

    except KeyboardInterrupt:
            sys.exit()
