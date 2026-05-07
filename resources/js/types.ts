export interface PdfField {
    id: number
    pdf_document_id: number
    pdf_page_id: number
    field_name: string
    field_type: string
    page_number: number
    pdf_left: number
    pdf_top: number
    pdf_width: number
    pdf_height: number
    css_left: number
    css_top: number
    css_width: number
    css_height: number
    font: string | null
    font_size: number | null
    font_weight: string
    text_align: string
    data_type: string
    text_color: string | null
    background_color: string | null
    border_color: string | null
    border_style: string | null
    border_width: number | null
    value: string | null
}

export interface PdfPage {
    id: number
    pdf_document_id: number
    page_number: number
    fields: PdfField[]
}

export interface PdfDocument {
    id: number
    original_name: string
    page_count: number | null
    status: string
    pages: PdfPage[]
    fields: PdfField[]
}

export interface Flash {
    success?: string
    error?: string
}
