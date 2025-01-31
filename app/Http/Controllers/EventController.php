<?php

namespace App\Http\Controllers;

use App\Models\Config;
use App\Models\Profesor;
use App\Models\Event;
use App\Models\Horario;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function index(){}

    public function create(){}

    public function store(Request $request)
    {
        // dd($request->all());
        // Validar los datos de la solicitud
        $request->validate([
            'fecha_reserva' => 'required',
            'hora_reserva' => 'required',
            'profesor_id' => 'required|exists:profesors,id'
        ]);

        // Buscar el profesor por su ID
        $profesor = Profesor::find($request->profesor_id);
        $fecha_reserva = $request->fecha_reserva;
        $hora_reserva = $request->hora_reserva . ':00'; // Asegurarse de que la hora esté en formato correcto

        // Obtener el día de la semana en español
        $dia = date('l', strtotime($fecha_reserva));
        $dia_de_reserva = $this->traducir_dia($dia);

        // Consultar los horarios disponibles del profesor
        $horarios = Horario::where('profesor_id', $profesor->id)
            ->where('dia', $dia_de_reserva)
            ->where('hora_inicio', '<=', $hora_reserva)
            ->where('hora_fin', '>=', $hora_reserva)
            ->exists();
        // dd($horarios);
        if ($horarios) {
            return redirect()->back()->with([
                'info' => 'El profesor no está disponible en ese horario.',
                'icono' => 'error',
                'hora_reserva' => 'El profesor no está disponible en ese horario.',
            ]);
        }

        //  dd($horarios);
        $fecha_hora_reserva = $fecha_reserva . " " . $hora_reserva;

        /// valida si existen eventos duplicado
        $eventos_duplicados = Event::where('profesor_id', $profesor->id)
            ->where('start', $fecha_hora_reserva)
            ->exists();

        if ($eventos_duplicados) {
            return redirect()->back()->with([
                'info' => 'Ya existe una reserva con el mismo profesor en esa fecha y hora.',
                'icono' => 'error',
                'title' => 'Ya existe una reserva con el mismo profesor en esa fecha y hora.',
            ]);
        }

        // Crear una nueva instancia de Event
        $evento = new Event();
        $evento->title = $request->hora_reserva . " " . $profesor->especialidad;
        $evento->start = $request->fecha_reserva . " " . $hora_reserva;
        $evento->end = $request->fecha_reserva . " " . $hora_reserva;
        $evento->color = '#e82216';
        $evento->user_id = Auth::user()->id;
        $evento->profesor_id  = $request->profesor_id;
        $evento->curso_id   = '1';
        $evento->save();

        // Redirigir con un mensaje de éxito
        return redirect()->route('admin.index')
            ->with('info', 'Se registró la reserva de la cita médica de forma correcta.')
            ->with('icono', 'success')
            ->with('title', 'Éxito');
    }

    private function traducir_dia($dia)
    {
        $dias = [
            'Monday' => 'LUNES',
            'Tuesday' => 'MARTES',
            'Wednesday' => 'MIERCOLES',
            'Thursday' => 'JUEVES',
            'Friday' => 'VIERNES',
            'Saturday' => 'SABADO',
            'Sunday' => 'DOMINGO',
        ];
        return $dias[$dia] ?? $dias;
    }
    // public function show(Event $event){return response()->json($event);}

    public function show(Request $request)
    {
        try {
            // Aquí puedes obtener todos los eventos desde la base de datos
            // $events = Event::all(); // Cambia esto según la lógica que necesites
             $events = Event::with('profesor', 'cliente')->get(); // Carga la relación 'profesor'
            
            return response()->json($events); // Devuelve todos los eventos
        } catch (\Exception $e) {
            \Log::error($e); // Loguea el error para diagnóstico
            return response()->json(['error' => 'Error al obtener eventos'], 500);
        }
    }
    

    public function update(Request $request, Event $event){
        $validatedData = $request->validate(['profesor_id' => 'required','hora_reserva' => 'required','fecha_reserva' => 'required|date']);
        $event->update($validatedData);
        return response()->json(['message' => 'Evento actualizado correctamente']);
    }

    public function destroy(Event $event)
    {
        $event->delete(); // Cambiar destroy() por delete()
        return redirect()->back()->with([
            'mensaje' => 'Se eliminó la reserva de manera correcta',
            'icono' => 'success',
        ]);
        // return response()->json(['message' => 'Evento eliminado exitosamente']);
    }

    // public function reportes(){
    //     return view('admin.reservas.reportes');
    // }

    public function pdf(){
        $configuracion = Config::latest()->first();
        $eventos = Event::all();

        $pdf = Pdf::loadView('admin.reservas.pdf', compact('configuracion','eventos'));

        // Incluir la numeración de páginas y el pie de página
        $pdf->output();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $canvas->page_text(20, 800, "Impreso por: ".Auth::user()->email, null, 10, array(0,0,0));
        $canvas->page_text(270, 800, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 10, array(0,0,0));
        $canvas->page_text(450, 800, "Fecha: " . \Carbon\Carbon::now()->format('d/m/Y')." - ".\Carbon\Carbon::now()->format('H:i:s'), null, 10, array(0,0,0));


        return $pdf->stream();
    }

    // public function pdf_fechas(Request $request){
    //     //$datos = request()->all();
    //     //return response()->json($datos);

    //     $configuracion = Configuracione::latest()->first();

    //     $fecha_inicio = $request->input('fecha_inicio');
    //     $fecha_fin = $request->input('fecha_fin');

    //     $eventos = Event::whereBetween('start',[$fecha_inicio, $fecha_fin])->get();

    //     $pdf = \PDF::loadView('admin.reservas.pdf_fechas', compact('configuracion','eventos','fecha_inicio','fecha_fin'));

    //     // Incluir la numeración de páginas y el pie de página
    //     $pdf->output();
    //     $dompdf = $pdf->getDomPDF();
    //     $canvas = $dompdf->getCanvas();
    //     $canvas->page_text(20, 800, "Impreso por: ".Auth::user()->email, null, 10, array(0,0,0));
    //     $canvas->page_text(270, 800, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 10, array(0,0,0));
    //     $canvas->page_text(450, 800, "Fecha: " . \Carbon\Carbon::now()->format('d/m/Y')." - ".\Carbon\Carbon::now()->format('H:i:s'), null, 10, array(0,0,0));


    //     return $pdf->stream();
    // }
}
