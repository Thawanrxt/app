"use client";

import { useEffect } from "react";
import { MapContainer, TileLayer, Marker, Popup, useMap } from "react-leaflet";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

type PlotMapData = {
  plot_id: string;
  farm_id: string;
  plot_name: string;
  lat: number;
  lng: number;
  has_location: boolean;
  area_rai: number;
  area_ngan: number;
  area_sq_wa: number;
  has_plan: boolean;
  plan_id: string | null;
  rice_name: string | null;
  season_type: string | null;
  start_date: string | null;
  harvest_date: string | null;
};

type Props = {
  plots: PlotMapData[];
  selectedPlot: PlotMapData | null;
  onSelectPlot: (plot: PlotMapData) => void;
};

// สร้าง custom icon สีเขียว (มีแผน) และสีส้ม (ไม่มีแผน)
function createIcon(hasplan: boolean) {
  const color = hasplan ? "#2e7d32" : "#f57c00";
  const svg = encodeURIComponent(`
    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="42" viewBox="0 0 32 42">
      <path d="M16 0C7.163 0 0 7.163 0 16c0 10.5 16 26 16 26s16-15.5 16-26C32 7.163 24.837 0 16 0z" fill="${color}"/>
      <circle cx="16" cy="16" r="7" fill="white"/>
      <circle cx="16" cy="16" r="4" fill="${color}"/>
    </svg>
  `);
  return new L.Icon({
    iconUrl: `data:image/svg+xml,${svg}`,
    iconSize: [32, 42],
    iconAnchor: [16, 42],
    popupAnchor: [0, -42],
  });
}

// เลื่อนแผนที่ไปยัง plot ที่เลือก
function FlyTo({ plot }: { plot: PlotMapData | null }) {
  const map = useMap();
  useEffect(() => {
    if (plot && plot.lat && plot.lng) {
      map.flyTo([plot.lat, plot.lng], 15, { duration: 0.8 });
    }
  }, [plot, map]);
  return null;
}

export default function MapView({ plots, selectedPlot, onSelectPlot }: Props) {
  // หาจุดกึ่งกลางของแปลงทั้งหมด
  const centerLat = plots.length > 0 ? plots.reduce((s, p) => s + p.lat, 0) / plots.length : 13.7563;
  const centerLng = plots.length > 0 ? plots.reduce((s, p) => s + p.lng, 0) / plots.length : 100.5018;

  return (
    <>
      <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
      <MapContainer
        center={[centerLat, centerLng]}
        zoom={plots.length === 1 ? 14 : 11}
        style={{ height: "100%", width: "100%" }}
        zoomControl={true}
      >
        <TileLayer
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
          attribution='&copy; <a href="https://openstreetmap.org">OpenStreetMap</a>'
        />

        <FlyTo plot={selectedPlot} />

        {plots.map((plot) => (
          <Marker
            key={plot.plot_id}
            position={[plot.lat, plot.lng]}
            icon={createIcon(plot.has_plan)}
            eventHandlers={{
              click: () => onSelectPlot(plot),
            }}
          >
            <Popup>
              <div style={{ minWidth: 160, fontFamily: "sans-serif" }}>
                <div style={{ fontWeight: "bold", fontSize: 14, marginBottom: 4 }}>
                  {plot.plot_name}
                </div>
                <div style={{ fontSize: 12, color: "#555" }}>{plot.farm_id}</div>
                {plot.rice_name && (
                  <div style={{ fontSize: 12, marginTop: 4 }}>
                    🌾 {plot.rice_name}
                  </div>
                )}
                {plot.area_rai > 0 && (
                  <div style={{ fontSize: 12 }}>📐 {plot.area_rai} ไร่</div>
                )}
              </div>
            </Popup>
          </Marker>
        ))}
      </MapContainer>
    </>
  );
}
