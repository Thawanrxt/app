"use client";

import { MapContainer, TileLayer, Marker, useMapEvents } from "react-leaflet";
import { useState } from "react";
import "leaflet/dist/leaflet.css";
import L from "leaflet";

// Fix marker icon
delete (L.Icon.Default.prototype as any)._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl:
    "https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png",
  iconUrl:
    "https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png",
  shadowUrl:
    "https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png",
});

function LocationMarker({ onSelect }: any) {
  const [position, setPosition] = useState<any>(null);

  useMapEvents({
    click(e: any) {
      setPosition(e.latlng);   // แสดง marker
      onSelect(e.latlng);      // 🔥 ส่งค่ากลับออกไป
    },
  });

  return position ? <Marker position={position} /> : null;
}

export default function MapPicker({ onSelect }: any) {
  return (
    <div>
      <MapContainer
        center={[13.7563, 100.5018]}
        zoom={13}
        style={{ height: "250px", borderRadius: "12px" }}
      >
        <TileLayer
          attribution="© OpenStreetMap"
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />

        {/* 👇 ส่ง onSelect เข้าไป */}
        <LocationMarker onSelect={onSelect} />
      </MapContainer>
    </div>
  );
}