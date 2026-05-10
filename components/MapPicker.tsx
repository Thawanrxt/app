"use client";

import { MapContainer, TileLayer, Marker, useMapEvents, useMap } from "react-leaflet";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

type Props = {
  lat: number;
  lng: number;
  onChange: (lat: number, lng: number) => void;
};

function LocationMarker({ lat, lng, onChange }: Props) {
  // 🌟 ย้ายการสร้าง icon มาไว้ด้านใน เพื่อป้องกัน error "window is not defined" ใน Next.js
  const icon = new L.Icon({
    iconUrl: "https://unpkg.com/leaflet@1.9.3/dist/images/marker-icon.png",
    iconSize: [25, 41],
    iconAnchor: [12, 41],
  });

  useMapEvents({
    click(e) {
      onChange(e.latlng.lat, e.latlng.lng);
    },
  });

  return <Marker position={[lat, lng]} icon={icon} />;
}

// auto move map
function Recenter({ lat, lng }: { lat: number; lng: number }) {
  const map = useMap();
  map.setView([lat, lng]);
  return null;
}

export default function MapPicker({ lat, lng, onChange }: Props) {
  // 🌟 กันเหนียวด้วยพิกัดสำรอง กรณีค่าว่าง ระบบแผนที่จะได้ไม่พัง
  const safeLat = lat || 13.736717;
  const safeLng = lng || 100.523186;

  return (
    <div style={{ height: "100%", width: "100%", minHeight: "300px", position: "relative", zIndex: 1 }}>
      {/* 🌟 บังคับโหลด CSS ของ Leaflet ป้องกันปัญหา Next.js โหลด CSS ไม่ขึ้นจนเป็นหน้าขาว */}
      <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
      <MapContainer
        center={[safeLat, safeLng]}
        zoom={13}
        style={{ height: "100%", width: "100%", minHeight: "300px" }}
      >
        <TileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />
        <Recenter lat={safeLat} lng={safeLng} />
        <LocationMarker lat={safeLat} lng={safeLng} onChange={onChange} />
      </MapContainer>
    </div>
  );
}