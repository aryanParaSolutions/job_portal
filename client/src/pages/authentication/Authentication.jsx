import React from "react";

export default function Authentication() {
  return (
    <div className="flex items-center justify-center h-screen w-full">
      <div className="flex flex-col gap-6 p-5 bg-gray-200 rounded-md">
        <input
          type="text"
          className="text-sm px-2 py-1 rounded-sm focus:outline-0 focus:ring-1 ring-blue-300"
        />

        <input
          type="text"
          className="text-sm px-2 py-1 rounded-sm focus:outline-0 focus:ring-1 ring-blue-300"
        />

        <input
          type="text"
          className="text-sm px-2 py-1 rounded-sm focus:outline-0 focus:ring-1 ring-blue-300"
        />
      </div>
    </div>
  );
}
